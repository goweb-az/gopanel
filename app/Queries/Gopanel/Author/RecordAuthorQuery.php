<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Author;

use App\Enums\System\AuthorRoleEnum;
use App\Models\Author\RecordAuthor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Bir sətrin `record_authors` oxunuşları.
 *
 * Niyə ayrıca Query: servis yazma ardıcıllığını idarə edir, oxu isə həm
 * servisdə (yarış yoxlaması), həm panel tarixçəsində lazımdır - bir yerdə
 * saxlanılır ki, «hazırkı» şərti (`whereNotNull('is_current')`) iki cür yazılmasın.
 */
final class RecordAuthorQuery
{
    public function __construct(
        private readonly Model $record,
    ) {
    }

    /** Hazırkı qeyd - yoxdursa `null`. */
    public function current(AuthorRoleEnum $role): ?RecordAuthor
    {
        return $this->base($role)->current()->first();
    }

    /**
     * Rol üzrə tam tarixçə - yenidən köhnəyə doğru.
     *
     * Limit var: bir sətrin əl dəyişməsi praktikada onlarla olur, amma
     * qeyri-məhdud `get()` starter qaydasına ziddir.
     *
     * @return Collection<int, RecordAuthor>
     */
    public function history(AuthorRoleEnum $role = AuthorRoleEnum::ASSIGNEE, int $limit = 100): Collection
    {
        return $this->base($role)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function base(AuthorRoleEnum $role): \Illuminate\Database\Eloquent\Builder
    {
        return RecordAuthor::query()
            ->with(['admin', 'user'])
            ->where('authorable_type', $this->record->getMorphClass())
            ->where('authorable_id', $this->record->getKey())
            ->role($role);
    }
}
