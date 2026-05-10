<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
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
}; ?>

<div class="container-fluid p-4">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Livewire v4 — Component Probe (SFC)</h4>

            <hr><h6>1. Counter</h6>
            <button type="button" class="btn btn-primary btn-sm" wire:click="increment">Increment</button>
            <span class="badge bg-success ms-2">Count: {{ $count }}</span>

            <hr><h6>2. Toast bridge</h6>
            <button type="button" class="btn btn-success btn-sm" wire:click="fireToast('success')">Success</button>
            <button type="button" class="btn btn-warning btn-sm" wire:click="fireToast('warning')">Warning</button>
            <button type="button" class="btn btn-danger btn-sm" wire:click="fireToast('error')">Error</button>

            <hr><h6>3. Modal</h6>
            <button type="button" class="btn btn-info btn-sm" wire:click="$set('modalOpen', true)">Open modal</button>
            <x-gopanel.modal name="probe-modal" title="Probe modal" wireOpen="modalOpen">
                <p>Alpine open/close via $entangle.</p>
                <input type="text" class="form-control" placeholder="Type here">
                <x-slot:footer>
                    <button type="button" class="btn btn-secondary" x-on:click="isOpen = false">Close</button>
                </x-slot:footer>
            </x-gopanel.modal>

            <hr><h6>4. Sortable (current order: {{ implode(',', $sortableIds) }})</h6>
            <x-gopanel.sortable wireMethod="reorder" class="list-group" style="max-width: 300px;">
                @foreach ($sortableIds as $id)
                    <div data-id="{{ $id }}" class="list-group-item" wire:key="row-{{ $id }}">
                        <i class="mdi mdi-drag"></i> Item #{{ $id }}
                    </div>
                @endforeach
            </x-gopanel.sortable>

            <hr><h6>5. File upload</h6>
            <x-gopanel.file-upload name="upload" label="Upload an image" />
            @if ($upload)
                <div class="text-success small">Temp file received.</div>
            @endif

            <hr><h6>6. Select2 (selected: {{ $select2Value ?: '—' }})</h6>
            <x-gopanel.select2
                name="select2Value"
                label="Pick one"
                placeholder="Choose..."
                :options="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']"
            />

            <hr><h6>7. Icon picker (selected: <code>{{ $iconValue ?: '—' }}</code>)</h6>
            <x-gopanel.icon-picker name="iconValue" />
        </div>
    </div>
</div>
