<div class="container-fluid p-4">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Livewire v4 — Phase 1 Component Probe</h4>
            <p class="text-muted">Each section verifies one shared component works end-to-end.</p>

            {{-- 1. Livewire counter --}}
            <hr><h6>1. Livewire counter</h6>
            <button type="button" class="btn btn-primary btn-sm" wire:click="increment">Increment</button>
            <span class="badge bg-success ms-2">Count: {{ $count }}</span>

            {{-- 2. Toast bridge --}}
            <hr><h6>2. Toast bridge</h6>
            <button type="button" class="btn btn-success btn-sm" wire:click="fireToast('success')">Success toast</button>
            <button type="button" class="btn btn-warning btn-sm" wire:click="fireToast('warning')">Warning toast</button>
            <button type="button" class="btn btn-danger btn-sm" wire:click="fireToast('error')">Error toast</button>

            {{-- 3. Modal --}}
            <hr><h6>3. Modal</h6>
            <button type="button" class="btn btn-info btn-sm" wire:click="$set('modalOpen', true)">Open modal</button>
            <x-gopanel.modal name="probe-modal" title="Probe modal" wireOpen="modalOpen">
                <p>This modal uses Alpine open/close + Livewire $entangle.</p>
                <input type="text" class="form-control" placeholder="Type here">
                <x-slot:footer>
                    <button type="button" class="btn btn-secondary" x-on:click="isOpen = false">Close</button>
                </x-slot:footer>
            </x-gopanel.modal>

            {{-- 4. Sortable --}}
            <hr><h6>4. Sortable (drag rows; current order: {{ implode(',', $sortableIds) }})</h6>
            <x-gopanel.sortable wireMethod="reorder" class="list-group" style="max-width: 300px;">
                @foreach ($sortableIds as $id)
                    <div data-id="{{ $id }}" class="list-group-item" wire:key="row-{{ $id }}">
                        <i class="mdi mdi-drag"></i> Item #{{ $id }}
                    </div>
                @endforeach
            </x-gopanel.sortable>

            {{-- 5. File upload --}}
            <hr><h6>5. File upload</h6>
            <x-gopanel.file-upload name="upload" label="Upload an image" />
            @if ($upload)
                <div class="text-success small">Uploaded temp file received by Livewire.</div>
            @endif

            {{-- 6. Select2 --}}
            <hr><h6>6. Select2 (selected: {{ $select2Value ?: '—' }})</h6>
            <x-gopanel.select2
                name="select2Value"
                label="Pick one"
                placeholder="Choose..."
                :options="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']"
            />

            {{-- 7. Icon picker --}}
            <hr><h6>7. Icon picker (selected: <code>{{ $iconValue ?: '—' }}</code>)</h6>
            <x-gopanel.icon-picker name="iconValue" />

            <hr>
            <div x-data="{ alpine: 'OK' }">
                <strong>Alpine:</strong> <span x-text="alpine"></span>
            </div>
        </div>
    </div>
</div>
