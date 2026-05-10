<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('gopanel.layouts.main')]
class TestProbe extends Component
{
    use WithFileUploads;

    public int $count = 0;

    public bool $modalOpen = false;

    public string $select2Value = '';

    public string $iconValue = '';

    public mixed $upload = null;

    public array $sortableIds = ['1', '2', '3'];

    public function increment(): void
    {
        $this->count++;
    }

    public function fireToast(string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: "Toast {$type} fired at " . now()->format('H:i:s'));
    }

    public function reorder(array $ids): void
    {
        $this->sortableIds = $ids;
        $this->dispatch('notify', type: 'info', message: 'Reordered: ' . implode(', ', $ids));
    }

    public function render()
    {
        return view('livewire.test-probe');
    }
}
