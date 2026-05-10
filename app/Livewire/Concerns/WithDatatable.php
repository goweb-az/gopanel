<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Drop-in datatable behaviour for an SFC Livewire component.
 *
 * The host component MUST define:
 *   - protected function datatableQuery(): Builder
 *   - protected function datatableColumns(): array
 *       Each entry: ['key' => 'title', 'label' => 'Başlıq', 'sortable' => true]
 *
 * The view should render <x-gopanel.datatable :rows="$this->rows" :columns="$this->columns" ...>.
 */
trait WithDatatable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url]
    public string $sortField = 'id';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url(as: 'pp', except: 15)]
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        $query = $this->datatableQuery();

        if ($this->search !== '') {
            $this->applySearch($query, $this->search);
        }

        $sortable = collect($this->datatableColumns())
            ->where('sortable', true)
            ->pluck('key')
            ->push('id', 'created_at', 'updated_at')
            ->all();

        if (in_array($this->sortField, $sortable, true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($this->perPage);
    }

    public function getColumnsProperty(): array
    {
        return $this->datatableColumns();
    }

    /**
     * Default search across the columns flagged with 'searchable' => true.
     * Override in the host component for joins, full-text, JSON paths, etc.
     */
    protected function applySearch(Builder $query, string $term): void
    {
        $searchable = collect($this->datatableColumns())
            ->where('searchable', true)
            ->pluck('key')
            ->all();

        if (empty($searchable)) {
            return;
        }

        $query->where(function (Builder $q) use ($searchable, $term) {
            foreach ($searchable as $col) {
                $q->orWhere($col, 'like', "%{$term}%");
            }
        });
    }
}
