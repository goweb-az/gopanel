<?php

namespace Tests\Feature\Gopanel\Authors;

use App\Enums\System\AuthorEndReasonEnum;
use App\Enums\System\AuthorRoleEnum;
use App\Enums\System\AuthorSourceEnum;
use App\Exceptions\Domain\System\RecordAlreadyAssigned;
use App\Exceptions\Domain\System\RecordAssignmentDenied;
use App\Helpers\Gopanel\AuthorCell;
use App\Models\Activity\FileLog;
use App\Models\Author\RecordAuthor;
use App\Models\Gopanel\Admin;
use App\Services\Author\RecordAuthorService;
use App\Support\Gopanel\AssignmentGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Gopanel\Authors\Fixtures\PlainTicket;
use Tests\Feature\Gopanel\Authors\Fixtures\TestTicket;
use Tests\Feature\Gopanel\Authors\Fixtures\TestTicketController;
use Tests\Feature\Gopanel\Concerns\BootsPanelSqlite;
use Tests\TestCase;

/**
 * Qeyd müəllifi / məsulu (HasAuthors + RecordAuthorService + HandlesAssignment).
 * Baza: yaddaşda SQLite - dev bazaya heç nə yazılmır.
 */
class RecordAuthorsTest extends TestCase
{
    use BootsPanelSqlite;

    private const PREFIX = 'gopanel.test-tickets';

    private RecordAuthorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPanelSqlite(activityLog: true);

        (require base_path('database/migrations/2026_10_25_100001_create_record_authors_table.php'))->up();

        Schema::create('test_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        $this->service = app(RecordAuthorService::class);
    }

    public function test_creator_is_written_only_for_opted_in_models(): void
    {
        $admin = $this->makePanelAdmin('creator@example.com', name: 'Oruc Seyidov');
        Auth::guard('gopanel')->login($admin);

        $ticket = TestTicket::query()->create(['title' => 'Birinci']);
        PlainTicket::query()->create(['title' => 'Trait-siz']);

        $this->assertSame(1, RecordAuthor::query()->count(), 'Trait-siz model müəllif qeydi yaratmamalıdır.');

        $creator = $ticket->fresh()->creator;
        $this->assertSame(AuthorRoleEnum::CREATOR, $creator->role);
        $this->assertSame(AuthorSourceEnum::PANEL, $creator->source);
        $this->assertSame($admin->id, $creator->admin_id);
        $this->assertSame('Oruc Seyidov', $creator->display_name);

        // Təkrar çağırış ikinci müəllif yaratmır
        $this->assertNull($this->service->setCreator($ticket));
        $this->assertSame(1, RecordAuthor::query()->count());
    }

    public function test_source_falls_back_to_system_and_respects_explicit_context(): void
    {
        $system = TestTicket::query()->create(['title' => 'Cron']);
        $imported = RecordAuthorService::using(
            AuthorSourceEnum::IMPORT,
            fn () => TestTicket::query()->create(['title' => 'İdxal']),
        );

        $this->assertSame(AuthorSourceEnum::SYSTEM, $system->creator->source);
        $this->assertSame('Sistem', $system->creator->display_name);
        $this->assertSame(AuthorSourceEnum::IMPORT, $imported->creator->source);
        $this->assertSame('İdxal', $imported->creator->display_name);
    }

    public function test_claim_is_idempotent_for_owner_and_rejected_for_others(): void
    {
        [$first, $second] = $this->twoAdmins();
        $ticket = TestTicket::query()->create(['title' => 'Müraciət']);

        $claimed = $this->service->claim($ticket, $first);
        $again = $this->service->claim($ticket, $first);

        $this->assertSame($claimed->id, $again->id);
        $this->assertSame(1, RecordAuthor::query()->role(AuthorRoleEnum::ASSIGNEE)->count());

        $this->expectException(RecordAlreadyAssigned::class);
        $this->service->claim($ticket, $second);
    }

    public function test_unique_index_blocks_second_current_assignee(): void
    {
        [$first, $second] = $this->twoAdmins();
        $ticket = TestTicket::query()->create(['title' => 'Yarış']);
        $this->service->claim($ticket, $first);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Servisin yoxlamasını keçən paralel sorğu - DB indeksi tutmalıdır
        DB::table('record_authors')->insert([
            'uid'             => (string) \Illuminate\Support\Str::uuid(),
            'authorable_type' => $ticket->getMorphClass(),
            'authorable_id'   => $ticket->id,
            'role'            => 'assignee',
            'admin_id'        => $second->id,
            'source'          => 'panel',
            'is_current'      => 1,
        ]);
    }

    public function test_release_closes_row_and_only_owner_can_release(): void
    {
        [$first, $second] = $this->twoAdmins();
        $ticket = TestTicket::query()->create(['title' => 'Buraxma']);
        $this->service->claim($ticket, $first);

        try {
            $this->service->release($ticket, $second);
            $this->fail('Başqasının qeydini buraxmaq olmamalıdır.');
        } catch (RecordAssignmentDenied) {
        }

        $this->assertTrue($this->service->release($ticket, $first));
        $this->assertFalse($this->service->release($ticket, $first), 'Boş qeydi buraxmaq false qaytarır.');

        $row = RecordAuthor::query()->role(AuthorRoleEnum::ASSIGNEE)->sole();
        $this->assertNull($row->is_current);
        $this->assertNotNull($row->ended_at);
        $this->assertSame(AuthorEndReasonEnum::RELEASED, $row->end_reason);
        $this->assertNull($ticket->fresh()->assignee);
    }

    public function test_takeover_keeps_history_and_marks_stale_as_expired(): void
    {
        [$first, $second] = $this->twoAdmins();
        $ticket = TestTicket::query()->create(['title' => 'Takeover']);

        $this->service->claim($ticket, $first);
        $this->travel(1)->minutes();
        $this->service->takeover($ticket, $second);

        $history = $this->service->history($ticket);
        $this->assertCount(2, $history);
        $this->assertSame($second->id, $history[0]->admin_id, 'Tarixçə yenidən köhnəyə sıralanır.');
        $this->assertSame(AuthorEndReasonEnum::REASSIGNED, $history[1]->end_reason);

        // TTL keçib - növbəti götürmə «vaxtı keçdi» kimi bağlanır
        config(['gopanel.assignment.ttl_hours' => 1]);
        $this->travel(2)->hours();
        $this->assertTrue($ticket->fresh()->assignee->isStale());

        $this->service->takeover($ticket, $first);
        $this->assertSame(
            AuthorEndReasonEnum::EXPIRED,
            RecordAuthor::query()->where('admin_id', $second->id)->sole()->end_reason,
        );
        $this->assertSame(3, $ticket->assignmentHistory()->count());
    }

    public function test_panel_endpoints_claim_takeover_and_release_with_logs(): void
    {
        $this->registerRoutes();
        $owner = $this->makePanelAdmin('owner@example.com', [self::PREFIX . '.assign'], name: 'Sahib Admin');
        $plain = $this->makePanelAdmin('plain@example.com', [self::PREFIX . '.assign'], name: 'Adi Admin');
        $boss = $this->makePanelAdmin('boss@example.com', [self::PREFIX . '.assign', self::PREFIX . '.assign.takeover'], name: 'Rəhbər');
        $ticket = TestTicket::query()->create(['title' => 'HTTP']);

        $this->actingAs($owner, 'gopanel')->postJson(route(self::PREFIX . '.assign', $ticket))
            ->assertOk()
            ->assertJson(['status' => 'success', 'assignee' => 'Sahib Admin']);

        $this->assertDatabaseHas('activity_log', ['event' => 'assigned', 'subject_id' => $ticket->id]);
        $this->assertTrue(FileLog::query()->where('channel', 'gopanel')->exists(), 'Fayl jurnalına da yazılmalıdır.');

        // Adi götürmə - 409, takeover icazəsiz - 403
        $this->actingAs($plain, 'gopanel')->postJson(route(self::PREFIX . '.assign', $ticket))
            ->assertStatus(409)
            ->assertJson(['status' => 'error', 'code' => 'RECORD_ALREADY_ASSIGNED']);

        $this->actingAs($plain, 'gopanel')->postJson(route(self::PREFIX . '.assign', $ticket), ['takeover' => 1])
            ->assertStatus(403)
            ->assertJson(['code' => 'RECORD_ASSIGNMENT_DENIED']);

        // Takeover icazəsi ilə - keçir və «reassigned» jurnallanır
        $this->actingAs($boss, 'gopanel')->postJson(route(self::PREFIX . '.assign', $ticket), ['takeover' => 1])
            ->assertOk()
            ->assertJson(['assignee' => 'Rəhbər']);

        $activity = Activity::query()->where('event', 'reassigned')->sole();
        $this->assertSame('Sahib Admin', $activity->properties['context']['previous_assignee']);

        // Route `can:` middleware-i olmadan da yalnız sahib buraxa bilir
        $this->actingAs($owner, 'gopanel')->postJson(route(self::PREFIX . '.release', $ticket))
            ->assertStatus(403);

        $this->actingAs($boss, 'gopanel')->postJson(route(self::PREFIX . '.release', $ticket))
            ->assertOk()
            ->assertJson(['message' => 'Qeyd buraxıldı.']);

        $this->assertDatabaseHas('activity_log', ['event' => 'released']);
    }

    public function test_assignment_guard_allows_only_assignee_and_super_admin(): void
    {
        [$first, $second] = $this->twoAdmins();
        $super = $this->makePanelAdmin('super@example.com', super: true);
        $ticket = TestTicket::query()->create(['title' => 'Qapı']);

        $this->assertFalse(AssignmentGuard::allows($ticket, $first), 'Heç kimin üzərində deyilsə dəyişmək olmaz.');
        $this->assertTrue(AssignmentGuard::allows($ticket, $super));
        $this->assertTrue(AssignmentGuard::allows(new PlainTicket(), $first), 'Trait-siz model qapını bağlamır.');
        $this->assertFalse(AssignmentGuard::allows($ticket, null));

        $this->service->claim($ticket, $first);
        $ticket = $ticket->fresh();

        $this->assertTrue(AssignmentGuard::allows($ticket, $first));
        $this->assertFalse(AssignmentGuard::allows($ticket, $second));

        $payload = AssignmentGuard::payload($ticket);
        $this->assertSame(AssignmentGuard::CODE, $payload['code']);
        $this->assertSame('error', $payload['status']);
        $this->assertSame($first->full_name, $payload['holder']);
    }

    public function test_author_cell_renders_tags_buttons_and_banner_by_permission(): void
    {
        $this->registerRoutes();
        $owner = $this->makePanelAdmin('cell-owner@example.com', [self::PREFIX . '.assign'], name: 'Sahib <b>Admin</b>');
        $other = $this->makePanelAdmin('cell-other@example.com', [self::PREFIX . '.assign'], name: 'Digər');
        $nobody = $this->makePanelAdmin('cell-nobody@example.com');

        Auth::guard('gopanel')->login($owner);
        $ticket = TestTicket::query()->create(['title' => 'Hüceyrə']);
        $ticket->load(['creator.admin', 'assignee.admin']);

        $line = AuthorCell::creatorLine($ticket);
        $this->assertStringContainsString('Əlavə etdi', $line);
        $this->assertStringContainsString('Sahib &lt;b&gt;Admin&lt;/b&gt;', $line, 'Ad escape olunmalıdır.');
        $this->assertSame('', AuthorCell::assigneeBadge($ticket));
        $this->assertStringContainsString('data-gp-assign-action', AuthorCell::claimButton($ticket, self::PREFIX, 'bilet'));
        $this->assertSame('free', AuthorCell::banner($ticket, self::PREFIX, 'bilet')['state']);

        $this->service->claim($ticket, $owner);
        $ticket = $ticket->fresh(['creator.admin', 'assignee.admin']);

        $this->assertSame('', AuthorCell::claimButton($ticket, self::PREFIX, 'bilet'), 'Öz qeydimdə düymə olmur.');
        $this->assertSame('mine', AuthorCell::banner($ticket, self::PREFIX, 'bilet')['state']);
        $this->assertStringContainsString('gp-author-tag--active', AuthorCell::assigneeBadge($ticket));

        Auth::guard('gopanel')->login($other);
        $this->assertSame('', AuthorCell::claimButton($ticket, self::PREFIX, 'bilet'), 'Takeover icazəsi yoxdur.');
        $banner = AuthorCell::banner($ticket, self::PREFIX, 'bilet');
        $this->assertSame('other', $banner['state']);
        $this->assertFalse($banner['show_takeover']);

        Auth::guard('gopanel')->login($nobody);
        $this->assertNull(AuthorCell::banner($ticket, self::PREFIX, 'bilet'));

        $rows = AuthorCell::historyRows($this->service->history($ticket));
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['current']);

        $html = view('gopanel.component.assignment-history', ['assignmentRows' => $rows])->render();
        $this->assertStringContainsString('hazırda', $html);
    }

    /** @return array{0: Admin, 1: Admin} */
    private function twoAdmins(): array
    {
        return [
            $this->makePanelAdmin('first@example.com', name: 'Birinci Admin'),
            $this->makePanelAdmin('second@example.com', name: 'İkinci Admin'),
        ];
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')->prefix('gopanel')->name('gopanel.')->group(function () {
            Route::middleware('gopanel')->prefix('test-tickets')->name('test-tickets.')->group(function () {
                Route::post('/{item}/assign', [TestTicketController::class, 'assign'])->name('assign');
                Route::post('/{item}/release', [TestTicketController::class, 'release'])->name('release');
            });
        });

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }
}
