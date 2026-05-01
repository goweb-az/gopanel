{{-- Admin Create/Edit Modal --}}
<div id="cerate-modal" class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-right-side">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Adminlər</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="form-wrap">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    İmtina et
                </button>
                <button type="button" class="btn btn-primary waves-effect waves-light" id="save-form-btn">
                    Yadda saxla
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Şifrə Dəyişdirmə Modalı --}}
<div id="changePasswordModal" class="modal fade" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title" id="changePasswordLabel">
                    <i class="fas fa-key me-1"></i> Şifrəni dəyiş
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="change-password-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new-password" class="form-label">Yeni Şifrə</label>
                        <input type="password" class="form-control" id="new-password" name="password" placeholder="Yeni şifrəni daxil edin" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label for="new-password-confirm" class="form-label">Şifrə Təkrar</label>
                        <input type="password" class="form-control" id="new-password-confirm" name="password_confirmation" placeholder="Yeni şifrəni təsdiq edin" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina et</button>
                    <button type="submit" class="btn btn-warning" id="save-password-btn">
                        <i class="fas fa-save me-1"></i> Şifrəni yenilə
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
