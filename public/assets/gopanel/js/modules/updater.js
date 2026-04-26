/**
 * Gopanel Auto-Update Module
 * GitHub manifest əsaslı yeniləmə sistemi
 */

let updaterData = null;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * Yeniləmələri yoxla
 */
function checkUpdates() {
    showLoading('GitHub ilə əlaqə qurulur...');
    hideSection('no-updates-section');
    hideSection('error-section');
    hideSection('updates-section');
    hideSection('apply-section');

    const btn = document.getElementById('btn-check-updates');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Yoxlanır...';

    fetch(routeUrl('gopanel.system.updates.check'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
    })
    .then(res => res.json())
    .then(response => {
        hideLoading();
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-refresh"></i> Yeniləmələri yoxla';

        if (response.status === 'error') {
            showError(response.message);
            return;
        }

        updaterData = response.data;

        if (!updaterData.has_updates) {
            showSection('no-updates-section');
            return;
        }

        renderUpdates(updaterData);
    })
    .catch(err => {
        hideLoading();
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-refresh"></i> Yeniləmələri yoxla';
        showError('Əlaqə xətası: ' + err.message);
    });
}

/**
 * Yeniləmələrin siyahısını render et
 */
function renderUpdates(data) {
    const container = document.getElementById('updates-container');
    container.innerHTML = '';

    data.updates.forEach((update, updateIndex) => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-1">
                        <span class="badge bg-info font-size-14">v${escapeHtml(update.version)}</span>
                        <span class="ms-2">${escapeHtml(update.description)}</span>
                    </h5>
                    <small class="text-muted">${escapeHtml(update.date)}</small>
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               id="select-all-${updateIndex}" 
                               onchange="toggleAllFiles(${updateIndex}, this.checked)" checked>
                        <label class="form-check-label" for="select-all-${updateIndex}">Hamısını seç</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Fayl</th>
                                <th style="width: 120px;">Əməliyyat</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 100px;">Hərəkət</th>
                            </tr>
                        </thead>
                        <tbody id="files-${updateIndex}">
                            ${renderFileRows(update, updateIndex)}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.appendChild(card);
    });

    showSection('updates-section');
    showSection('apply-section');
    updateSelectedCount();
}

/**
 * Fayl sətrlərini render et
 */
function renderFileRows(update, updateIndex) {
    const filesStatus = update.files_status || [];
    
    return update.files.map((file, fileIndex) => {
        const status = filesStatus[fileIndex] || { status: 'safe', has_conflict: false };
        const actionBadge = getActionBadge(file.action);
        const statusBadge = getStatusBadge(status);
        const isConflict = status.has_conflict;

        return `
            <tr class="${isConflict ? 'table-warning' : ''}">
                <td class="text-center">
                    <input class="form-check-input file-checkbox" type="checkbox" 
                           data-update="${updateIndex}" 
                           data-file="${fileIndex}"
                           data-path="${escapeHtml(file.path)}"
                           data-action="${escapeHtml(file.action)}"
                           data-version="${escapeHtml(update.version)}"
                           onchange="updateSelectedCount()"
                           ${isConflict ? '' : 'checked'}>
                </td>
                <td>
                    <code class="text-dark" style="font-size: 12px;">${escapeHtml(file.path)}</code>
                    ${isConflict ? '<br><small class="text-warning"><i class="bx bx-error"></i> Bu fayl lokal olaraq da dəyişdirilib</small>' : ''}
                </td>
                <td>${actionBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="showDiff('${escapeHtml(file.path)}')" title="Diff bax">
                        <i class="bx bx-git-compare"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Action badge (added/modified/deleted)
 */
function getActionBadge(action) {
    const badges = {
        'added':    '<span class="badge bg-success">Yeni</span>',
        'modified': '<span class="badge bg-primary">Dəyişmiş</span>',
        'deleted':  '<span class="badge bg-danger">Silinmiş</span>',
    };
    return badges[action] || `<span class="badge bg-secondary">${escapeHtml(action)}</span>`;
}

/**
 * Status badge (safe/conflict/new/delete)
 */
function getStatusBadge(status) {
    const badges = {
        'safe':     '<span class="badge bg-soft-success text-success">✅ Təhlükəsiz</span>',
        'conflict': '<span class="badge bg-soft-warning text-warning">⚠️ Konflikt</span>',
        'new':      '<span class="badge bg-soft-info text-info">🆕 Yeni fayl</span>',
        'delete':   '<span class="badge bg-soft-danger text-danger">🗑️ Silinəcək</span>',
    };
    return badges[status.status] || '<span class="badge bg-secondary">Bilinmir</span>';
}

/**
 * Hamısını seç/sil
 */
function toggleAllFiles(updateIndex, checked) {
    document.querySelectorAll(`.file-checkbox[data-update="${updateIndex}"]`).forEach(cb => {
        cb.checked = checked;
    });
    updateSelectedCount();
}

/**
 * Seçilmiş fayl sayını yenilə
 */
function updateSelectedCount() {
    const count = document.querySelectorAll('.file-checkbox:checked').length;
    const el = document.getElementById('selected-count');
    if (el) el.textContent = `${count} fayl seçilib`;
}

/**
 * Faylın diff-ini göstər
 */
function showDiff(path) {
    const modal = new bootstrap.Modal(document.getElementById('diffModal'));
    document.getElementById('diffModalLabel').textContent = path;
    document.getElementById('diff-content').classList.add('d-none');
    document.getElementById('diff-loading').classList.remove('d-none');
    document.getElementById('diff-local').textContent = '';
    document.getElementById('diff-remote').textContent = '';
    modal.show();

    fetch(routeUrl('gopanel.system.updates.diff'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ path: path }),
    })
    .then(res => res.json())
    .then(response => {
        document.getElementById('diff-loading').classList.add('d-none');
        document.getElementById('diff-content').classList.remove('d-none');

        if (response.status === 'error') {
            document.getElementById('diff-local').textContent = 'Xəta: ' + response.message;
            return;
        }

        const data = response.data;
        document.getElementById('diff-local').textContent = data.local_content || '(Fayl mövcud deyil)';
        document.getElementById('diff-remote').textContent = data.remote_content || '(Fayl mövcud deyil)';
    })
    .catch(err => {
        document.getElementById('diff-loading').classList.add('d-none');
        document.getElementById('diff-content').classList.remove('d-none');
        document.getElementById('diff-local').textContent = 'Əlaqə xətası: ' + err.message;
    });
}

/**
 * Seçilmiş faylları yenilə
 */
function applyUpdates() {
    const checkboxes = document.querySelectorAll('.file-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Heç bir fayl seçilməyib', 'warning');
        return;
    }

    // Konflikt olan faylları yoxla
    const conflictFiles = [];
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.classList.contains('table-warning')) {
            conflictFiles.push(cb.dataset.path);
        }
    });

    let confirmMsg = `${checkboxes.length} fayl yenilənəcək.`;
    if (conflictFiles.length > 0) {
        confirmMsg += `\n\n⚠️ ${conflictFiles.length} faylda konflikt var — lokal dəyişiklikləriniz itəcək:\n`;
        conflictFiles.forEach(f => confirmMsg += `• ${f}\n`);
    }
    confirmMsg += '\nDavam etmək istəyirsiniz?';

    if (!confirm(confirmMsg)) return;

    // Faylları versiyalara görə qruplama
    const filesByVersion = {};
    checkboxes.forEach(cb => {
        const version = cb.dataset.version;
        if (!filesByVersion[version]) {
            filesByVersion[version] = [];
        }
        filesByVersion[version].push({
            path: cb.dataset.path,
            action: cb.dataset.action,
        });
    });

    // Son versiyani tap
    const versions = Object.keys(filesByVersion).sort((a, b) => {
        return a.localeCompare(b, undefined, { numeric: true });
    });
    const targetVersion = versions[versions.length - 1];

    // Bütün faylları birləşdir
    const allFiles = [];
    versions.forEach(v => {
        filesByVersion[v].forEach(f => allFiles.push(f));
    });

    const btn = document.getElementById('btn-apply');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Yenilənir...';

    const progressSection = document.getElementById('apply-progress');
    progressSection.classList.remove('d-none');
    const progressBar = progressSection.querySelector('.progress-bar');
    progressBar.style.width = '30%';

    fetch(routeUrl('gopanel.system.updates.apply'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            files: allFiles,
            version: targetVersion,
        }),
    })
    .then(res => res.json())
    .then(response => {
        progressBar.style.width = '100%';

        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-download"></i> Seçilmişləri yenilə';
            progressSection.classList.add('d-none');
            progressBar.style.width = '0%';

            if (response.status === 'error') {
                showToast(response.message, 'error');
                return;
            }

            showToast(response.message, 'success');

            // Versiya badge-ı yenilə
            document.getElementById('current-version').textContent = 'v' + targetVersion;

            // Yenidən yoxla
            setTimeout(() => location.reload(), 1500);
        }, 500);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-download"></i> Seçilmişləri yenilə';
        progressSection.classList.add('d-none');
        showToast('Əlaqə xətası: ' + err.message, 'error');
    });
}

/**
 * Backup-dan geri al
 */
function rollback(backupId) {
    if (!confirm('Bu yeniləmə geri alınacaq. Davam etmək istəyirsiniz?')) return;

    fetch(routeUrl('gopanel.system.updates.rollback'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ backup_id: backupId }),
    })
    .then(res => res.json())
    .then(response => {
        if (response.status === 'error') {
            showToast(response.message, 'error');
            return;
        }

        showToast(response.message, 'success');
        setTimeout(() => location.reload(), 1500);
    })
    .catch(err => {
        showToast('Əlaqə xətası: ' + err.message, 'error');
    });
}

// ─── Helpers ────────────────────────────────────────────────

function showSection(id) {
    document.getElementById(id)?.classList.remove('d-none');
}

function hideSection(id) {
    document.getElementById(id)?.classList.add('d-none');
}

function showLoading(text) {
    document.getElementById('loading-text').textContent = text || 'Yüklənir...';
    showSection('loading-section');
}

function hideLoading() {
    hideSection('loading-section');
}

function showError(message) {
    document.getElementById('error-message').textContent = message;
    showSection('error-section');
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function routeUrl(name) {
    // Gopanel route helper — route adından URL yarat
    const routes = {
        'gopanel.system.updates.check':    '/gopanel/system/updates/check',
        'gopanel.system.updates.diff':     '/gopanel/system/updates/diff',
        'gopanel.system.updates.apply':    '/gopanel/system/updates/apply',
        'gopanel.system.updates.rollback': '/gopanel/system/updates/rollback',
    };
    return routes[name] || name;
}

function showToast(message, type) {
    // SweetAlert varsa istifadə et, yoxsa alert
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: message,
            icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });
    } else if (typeof toastr !== 'undefined') {
        toastr[type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success'](message);
    } else {
        alert(message);
    }
}
