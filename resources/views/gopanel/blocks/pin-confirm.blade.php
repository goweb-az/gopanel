{{--
    Həssas AJAX əməliyyatının PIN təsdiqi modalı.

    Layout-a yalnız ADMIN_PIN_ENABLED=true olanda qoşulur.
    `modules/security/admin-pin.js` 423 + `pin_required` cavabını tutub bu
    modalı açır, təsdiqdən sonra dayandırılmış sorğunu təkrarlayır.
--}}
<div class="modal fade" id="pin-confirm-modal" tabindex="-1" aria-hidden="true"
     data-url="{{ route('gopanel.pin.confirm') }}">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-lock-alt me-1"></i> PIN təsdiqi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>

            <form id="pin-confirm-form">
                <div class="modal-body">
                    <p class="text-muted font-size-13" data-pin-confirm-message>
                        Bu əməliyyat üçün PIN təsdiqi lazımdır.
                    </p>
                    <input type="password" inputmode="numeric" autocomplete="one-time-code"
                           class="form-control text-center" id="pin-confirm-input" name="pin"
                           placeholder="PIN" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">İmtina</button>
                    <button type="submit" class="btn btn-primary">Təsdiqlə</button>
                </div>
            </form>
        </div>
    </div>
</div>
