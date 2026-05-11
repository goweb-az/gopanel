<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

trait WithAnalyticsDateFilter
{
    #[Url(as: 'from', except: '')]
    public string $filterDateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $filterDateTo = '';

    public function updatedFilterDateFrom(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function updatedFilterDateTo(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    protected function applyDateFilter(Builder $query, string $column = 'created_at'): Builder
    {
        if ($this->filterDateFrom !== '') {
            $query->where($column, '>=', $this->filterDateFrom . ' 00:00:00');
        }

        if ($this->filterDateTo !== '') {
            $query->where($column, '<=', $this->filterDateTo . ' 23:59:59');
        }

        return $query;
    }
}
