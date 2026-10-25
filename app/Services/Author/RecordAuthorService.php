<?php

declare(strict_types=1);

namespace App\Services\Author;

use App\Enums\System\AuthorEndReasonEnum;
use App\Enums\System\AuthorRoleEnum;
use App\Enums\System\AuthorSourceEnum;
use App\Exceptions\Domain\System\RecordAlreadyAssigned;
use App\Exceptions\Domain\System\RecordAssignmentDenied;
use App\Helpers\Common\ActivityLogHelper;
use App\Models\Author\RecordAuthor;
use App\Models\Gopanel\Admin;
use App\Queries\Gopanel\Author\RecordAuthorQuery;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * `record_authors` üzərindəki bütün yazma əməliyyatları (müəllif + məsul).
 *
 * Qayda: qeyd HEÇ VAXT üzərinə yazılmır - tarixçə itməsin.
 *   claim()    → yalnız INSERT
 *   release()  → köhnə qeydi bağlayır (is_current = null, ended_at)
 *   takeover() → bir tranzaksiyada köhnəni bağlayır, sonra yenisini yazır
 *
 * Əks sıra («əvvəlcə yeni, sonra köhnə») unikal indeksə dəyər - bax
 * miqrasiyadakı `record_authors_current_unique`.
 *
 * Təyinat QIFIL DEYİL: oxumağı bağlamır. Dəyişdirən əməliyyatı bağlamaq
 * istəyən bölmə `App\Support\Gopanel\AssignmentGuard`-u ayrıca çağırır.
 */
final class RecordAuthorService
{
    /**
     * Konsol/idxal axınında mənbənin açıq verilməsi üçün kontekst.
     *
     * Niyə: `created` hadisəsi guard-lara baxır; idxal isə konsoldan işlədiyi
     * üçün «Sistem» kimi görünərdi. `using()` bunu düzəldir.
     */
    private static ?AuthorSourceEnum $contextSource = null;

    public function __construct(
        private readonly BaseRepository $repository,
    ) {
    }

    /**
     * Callback müddətində yaradılan sətirlərin mənbəyini təyin edir.
     *
     * @template TReturn
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function using(AuthorSourceEnum $source, callable $callback): mixed
    {
        $previous = self::$contextSource;
        self::$contextSource = $source;

        try {
            return $callback();
        } finally {
            self::$contextSource = $previous;
        }
    }

    /**
     * Sətrin müəllifini yazır - `HasAuthors` trait-i `created` hadisəsində çağırır.
     *
     * Təkrar çağırışda heç nə etmir: bir sətrin bir müəllifi olur.
     */
    public function setCreator(Model $record): ?RecordAuthor
    {
        if ($this->current($record, AuthorRoleEnum::CREATOR) !== null) {
            return null;
        }

        $causer = ActivityLogHelper::resolveCauser();

        return $this->write($record, AuthorRoleEnum::CREATOR, $causer, $this->resolveSource($causer));
    }

    /**
     * Sətri adminin üzərinə götürür.
     *
     * @throws RecordAlreadyAssigned sətir artıq başqasının üzərindədirsə
     * @throws RecordAssignmentDenied panel hesabı yoxdursa
     */
    public function claim(Model $record, ?Admin $admin = null): RecordAuthor
    {
        $admin = $this->requireAdmin($admin);
        $current = $this->current($record, AuthorRoleEnum::ASSIGNEE);

        if ($current !== null) {
            if ((int) $current->admin_id === (int) $admin->getKey()) {
                return $current;
            }

            throw new RecordAlreadyAssigned(
                'Bu qeyd artıq ' . $current->display_name . ' adlı istifadəçinin üzərindədir.',
            );
        }

        try {
            return $this->write($record, AuthorRoleEnum::ASSIGNEE, $admin, AuthorSourceEnum::PANEL);
        } catch (QueryException $e) {
            // Yarış: iki admin eyni anda «götür» basıb. Unikal indeks
            // ikincisini rədd edir - istifadəçiyə anlaşılan mesaj verilir.
            if ($this->isDuplicate($e)) {
                throw new RecordAlreadyAssigned('Bu qeydi az əvvəl başqa istifadəçi öz üzərinə götürdü.', [], $e);
            }

            throw $e;
        }
    }

    /**
     * Admin işi buraxır - qeyd bağlanır, silinmir.
     *
     * @return bool qeyd ümumiyyətlə kiminsə üzərində idimi
     */
    public function release(Model $record, ?Admin $admin = null): bool
    {
        $current = $this->current($record, AuthorRoleEnum::ASSIGNEE);

        if ($current === null) {
            return false;
        }

        $admin ??= $this->guardAdmin();

        // Öz işini buraxmaq ayrı, başqasınınkını almaq ayrı əməliyyatdır -
        // buraxma yalnız sahibinə açıqdır.
        if ($admin instanceof Admin && (int) $current->admin_id !== (int) $admin->getKey()) {
            throw new RecordAssignmentDenied('Yalnız öz üzərinizdəki qeydi buraxa bilərsiniz.');
        }

        $this->close($current, AuthorEndReasonEnum::RELEASED);

        return true;
    }

    /**
     * Sətri başqasının üzərindən öz üzərinə götürür.
     *
     * İcazəni BURADA yoxlamır - çağıran (controller concern) `takeover`
     * icazəsini və ya təyinatın köhnəldiyini yoxlayır. Servis konsoldan da
     * (məs. işdən çıxan adminin qeydlərini köçürmək) çağırıla bilməlidir.
     */
    public function takeover(Model $record, ?Admin $admin = null): RecordAuthor
    {
        $admin = $this->requireAdmin($admin);

        return DB::transaction(function () use ($record, $admin): RecordAuthor {
            $current = $this->current($record, AuthorRoleEnum::ASSIGNEE);

            if ($current !== null) {
                if ((int) $current->admin_id === (int) $admin->getKey()) {
                    return $current;
                }

                $this->close(
                    $current,
                    $current->isStale() ? AuthorEndReasonEnum::EXPIRED : AuthorEndReasonEnum::REASSIGNED,
                );
            }

            return $this->write($record, AuthorRoleEnum::ASSIGNEE, $admin, AuthorSourceEnum::PANEL);
        });
    }

    /** Hazırkı qeyd - yoxdursa `null`. */
    public function current(Model $record, AuthorRoleEnum $role): ?RecordAuthor
    {
        return (new RecordAuthorQuery($record))->current($role);
    }

    /**
     * Rol üzrə tam tarixçə - yenidən köhnəyə doğru.
     *
     * @return Collection<int, RecordAuthor>
     */
    public function history(Model $record, AuthorRoleEnum $role = AuthorRoleEnum::ASSIGNEE): Collection
    {
        return (new RecordAuthorQuery($record))->history($role);
    }

    /** Yeni qeyd - hazırkı kimi. */
    private function write(Model $record, AuthorRoleEnum $role, ?object $causer, AuthorSourceEnum $source): RecordAuthor
    {
        $isAdmin = $causer instanceof Admin;

        // Repozitorinin qaytardığı `fresh()` nüsxəsi YOX, saxlanılan nüsxə
        // qaytarılır: `wasRecentlyCreated` yalnız onda `true`-dur və controller
        // «yeni təyinat, yoxsa təkrar klik» fərqini ona görə ayırır.
        $author = new RecordAuthor();

        $this->repository->save($author, [
            'authorable_type' => $record->getMorphClass(),
            'authorable_id'   => $record->getKey(),
            'role'            => $role->value,
            'admin_id'        => $isAdmin ? $causer->getKey() : null,
            'user_id'         => ! $isAdmin && $causer instanceof Model ? $causer->getKey() : null,
            'source'          => $source->value,
            'author_name'     => $causer !== null ? $this->nameOf($causer) : null,
            'is_current'      => 1,
            'assigned_at'     => now(),
        ]);

        return $author;
    }

    /**
     * Qeydi bağlayır - `is_current` `null` olur, `false`/`0` YOX (miqrasiyaya bax).
     */
    private function close(RecordAuthor $author, AuthorEndReasonEnum $reason): void
    {
        $this->repository->save($author, [
            'is_current' => null,
            'ended_at'   => now(),
            'end_reason' => $reason->value,
        ]);
    }

    /**
     * Ad snapshot-ı. `ActivityLogHelper::causerName()` cari guard-a baxır;
     * `claim($record, $admin)` isə başqa admin adından çağırıla bilər -
     * ona görə ad ötürülən obyektdən oxunur.
     */
    private function nameOf(object $causer): ?string
    {
        $name = trim((string) ($causer->full_name
            ?? trim(($causer->name ?? '') . ' ' . ($causer->surname ?? ''))));

        if ($name === '') {
            $name = (string) ($causer->email ?? '');
        }

        return $name !== '' ? $name : null;
    }

    /** Mənbə: açıq kontekst → inteqrasiya sorğusu → guard → sistem. */
    private function resolveSource(?object $causer): AuthorSourceEnum
    {
        if (self::$contextSource !== null) {
            return self::$contextSource;
        }

        try {
            // Törəmə layihənin inteqrasiya middleware-i bu atributu qoyur.
            if (request()->attributes->get('integration_client') !== null) {
                return AuthorSourceEnum::INTEGRATION;
            }
        } catch (Throwable) {
            // Konsolda `request()` olmaya bilər - mənbə guard-dan gəlir.
        }

        if ($causer instanceof Admin) {
            return AuthorSourceEnum::PANEL;
        }

        return $causer !== null ? AuthorSourceEnum::CUSTOMER : AuthorSourceEnum::SYSTEM;
    }

    private function requireAdmin(?Admin $admin): Admin
    {
        $admin ??= $this->guardAdmin();

        if (! $admin instanceof Admin) {
            throw new RecordAssignmentDenied('Bu əməliyyat üçün panel hesabı tələb olunur.');
        }

        return $admin;
    }

    private function guardAdmin(): ?Admin
    {
        try {
            $user = Auth::guard('gopanel')->user();
        } catch (Throwable) {
            return null;
        }

        return $user instanceof Admin ? $user : null;
    }

    /**
     * Unikal indeks pozuntusu - SQLSTATE 23000 (MySQL 1062, SQLite 19).
     * Kod driver-dən asılı olmasın deyə SQLSTATE yoxlanılır.
     */
    private function isDuplicate(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $sqlState === '23000' || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
