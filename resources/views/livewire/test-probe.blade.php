<div class="container-fluid p-4">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Livewire v4 Probe</h4>
            <p class="text-muted">If you can see the counter increment without a page reload, Livewire is wired correctly.</p>

            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="button" class="btn btn-primary" wire:click="increment">
                    Increment
                </button>
                <span class="badge bg-success" style="font-size:1rem;">
                    Count: {{ $count }}
                </span>
            </div>

            <hr>

            <div x-data="{ alpine: 'OK' }" class="mt-3">
                <strong>Alpine check:</strong> <span x-text="alpine"></span>
            </div>
        </div>
    </div>
</div>
