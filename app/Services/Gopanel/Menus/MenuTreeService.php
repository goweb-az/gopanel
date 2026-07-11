<?php

namespace App\Services\Gopanel\Menus;

use App\Models\Navigation\Menu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuTreeService
{
    public function __construct(
        private NavigationCacheService $cache
    ) {
    }

    public function maxDepth(): int
    {
        return (int) config('gopanel.menu.max_depth', 4);
    }

    /**
     * Bounded tree of menu items for a given position, including inactive items
     * (admin view). Roots are eager-loaded with their descendants down to the
     * configured max depth.
     */
    public function tree(string $position): Collection
    {
        $with = $this->nestedWith($this->maxDepth());

        return Menu::query()
            ->whereNull('parent_id')
            ->where('position', $position)
            ->with($with)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Move an item to a new parent/position and re-normalize the affected
     * sibling groups. Cycle and depth are validated inside the transaction.
     *
     * @return array{moved_id:int,new_parent_id:int|null,position:string}
     */
    public function move(array $data): array
    {
        $movedId        = (int) $data['moved_id'];
        $newParentId    = $data['new_parent_id'] ?? null;
        $newParentId    = $newParentId !== null && $newParentId !== '' ? (int) $newParentId : null;
        $position       = $data['position'];
        $siblings       = array_map('intval', $data['siblings'] ?? []);
        $sourceParentId = ($data['source_parent_id'] ?? null) !== null && $data['source_parent_id'] !== ''
            ? (int) $data['source_parent_id']
            : null;
        $sourceSiblings = array_map('intval', $data['source_siblings'] ?? []);

        $result = null;

        DB::transaction(function () use ($movedId, $newParentId, $position, $siblings, $sourceSiblings, &$result) {
            /** @var Menu $moved */
            $moved = Menu::query()->lockForUpdate()->findOrFail($movedId);

            $newParent = null;
            if ($newParentId !== null) {
                $newParent = Menu::query()->lockForUpdate()->findOrFail($newParentId);

                if ($newParent->id === $moved->id) {
                    throw new RuntimeException('Element özünü valideyn edə bilməz.');
                }

                if ($this->isDescendant($newParent->id, $moved->id)) {
                    throw new RuntimeException('Element öz alt elementinin altına köçürülə bilməz.');
                }
            }

            // Depth guard: deepest node of the moved subtree must stay within max depth.
            $parentDepth = $newParent ? $this->depthOf($newParent) : -1;
            $deepest = $parentDepth + 1 + $this->subtreeHeight($moved->id);
            if ($deepest > $this->maxDepth()) {
                throw new RuntimeException('Maksimum dərinlik həddi aşıldı.');
            }

            // Re-parent and re-position the moved item.
            $moved->parent_id = $newParentId;
            $moved->position  = $position;
            $moved->save();

            // Normalize destination sibling order (only ids that really belong there).
            $this->normalize($siblings, $newParentId, $position);

            // Normalize source sibling order if a distinct source group was provided.
            if (!empty($sourceSiblings)) {
                // Source siblings keep the moved item's *previous* parent/position,
                // which the frontend snapshot already reflects (moved item excluded).
                $this->normalizeExisting($sourceSiblings);
            }

            $result = [
                'moved_id'      => $moved->id,
                'new_parent_id' => $newParentId,
                'position'      => $position,
            ];
        });

        $this->cache->forget();

        return $result;
    }

    /**
     * Re-normalize the sort order of a sibling group after an in-place reorder.
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            Menu::query()->whereIn('id', $orderedIds)->lockForUpdate()->get();
            $this->normalizeExisting(array_map('intval', $orderedIds));
        });

        $this->cache->forget();
    }

    /**
     * Set sort_order = 0..n-1 for the given ordered ids, and also force their
     * parent_id/position to the destination group (used for the moved item's
     * new group).
     */
    private function normalize(array $orderedIds, ?int $parentId, string $position): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            Menu::query()->where('id', $id)->update([
                'parent_id'  => $parentId,
                'position'   => $position,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * Set sort_order = 0..n-1 without touching parent_id/position.
     */
    private function normalizeExisting(array $orderedIds): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            Menu::query()->where('id', $id)->update(['sort_order' => $order++]);
        }
    }

    private function isDescendant(int $candidateId, int $ancestorId): bool
    {
        foreach ($this->descendantIds($ancestorId) as $id) {
            if ($id === $candidateId) {
                return true;
            }
        }
        return false;
    }

    /**
     * All descendant ids of a node (breadth-first), bounded by max depth.
     */
    private function descendantIds(int $id): array
    {
        $ids = [];
        $frontier = [$id];
        $guard = 0;

        while (!empty($frontier) && $guard++ <= $this->maxDepth() + 1) {
            $children = Menu::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            if (empty($children)) {
                break;
            }
            foreach ($children as $childId) {
                $ids[] = (int) $childId;
            }
            $frontier = $children;
        }

        return $ids;
    }

    private function depthOf(Menu $menu): int
    {
        $depth = 0;
        $parentId = $menu->parent_id;
        $guard = 0;

        while ($parentId !== null && $guard++ <= $this->maxDepth() + 1) {
            $depth++;
            $parentId = Menu::query()->where('id', $parentId)->value('parent_id');
        }

        return $depth;
    }

    private function subtreeHeight(int $id): int
    {
        $childIds = Menu::query()->where('parent_id', $id)->pluck('id')->all();
        if (empty($childIds)) {
            return 0;
        }

        $max = 0;
        foreach ($childIds as $childId) {
            $max = max($max, 1 + $this->subtreeHeight((int) $childId));
        }
        return $max;
    }

    private function nestedWith(int $depth): array
    {
        // Build "childrenAdmin.childrenAdmin..." eager-load chain up to $depth.
        $relations = [];
        $path = 'childrenAdmin';
        for ($i = 0; $i < $depth; $i++) {
            $relations[] = $path;
            $path .= '.childrenAdmin';
        }
        return $relations;
    }
}
