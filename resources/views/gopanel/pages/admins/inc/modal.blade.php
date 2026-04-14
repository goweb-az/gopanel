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

@push('scripts')
<script>
$(function() {
    // Şifrə dəyişdirmə formu
    $('#change-password-form').on('submit', function(e) {
        e.preventDefault();
        var password = $('#new-password').val();
        var confirmation = $('#new-password-confirm').val();

        if (password.length < 6) {
            basicAlert('Şifrə ən az 6 simvol olmalıdır.', 'error');
            return;
        }
        if (password !== confirmation) {
            basicAlert('Şifrə təsdiqi uyğun gəlmir.', 'error');
            return;
        }

        // Admin formunun action URL-indən admin ID-ni alırıq
        var mainFormAction = $('#data-form').attr('action');
        if (!mainFormAction) {
            basicAlert('Admin məlumatı tapılmadı.', 'error');
            return;
        }

        var $btn = $('#save-password-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Gözləyin...');

        $.ajax({
            url: mainFormAction,
            type: 'POST',
            data: {
                password: password,
                password_confirmation: confirmation,
                _change_password_only: true
            },
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Şifrəni yenilə');
                basicAlert(response.message, response.status);
                if (response.status === 'success') {
                    $('#changePasswordModal').modal('hide');
                    $('#change-password-form')[0].reset();
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Şifrəni yenilə');
                showError(xhr);
            }
        });
    });

    // Modal bağlananda formu sıfırla
    $('#changePasswordModal').on('hidden.bs.modal', function() {
        $('#change-password-form')[0].reset();
    });
});
</script>
@endpush