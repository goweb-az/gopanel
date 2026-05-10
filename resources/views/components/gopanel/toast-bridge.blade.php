<div
    x-data
    x-init="
        const handler = (...args) => {
            console.log('[notify] received', args);
            // v4 dispatch with named args -> first arg is the named-args object
            // dispatch with positional -> args spread
            let data = args[0];
            if (Array.isArray(data)) data = data[0];
            if (typeof data !== 'object' || data === null) {
                data = { type: 'info', message: String(data ?? '') };
            }
            const type = data.type || 'info';
            const message = data.message || '';
            if (window.toastr && typeof window.toastr[type] === 'function') {
                window.toastr[type](message);
            } else if (window.toastr) {
                window.toastr.info(message);
            } else {
                console.warn('[toast-bridge] toastr not loaded');
            }
        };
        if (window.Livewire) {
            Livewire.on('notify', handler);
        } else {
            document.addEventListener('livewire:init', () => Livewire.on('notify', handler));
        }
    "
></div>
