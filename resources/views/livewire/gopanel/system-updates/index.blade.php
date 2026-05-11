<?php

use App\Services\Gopanel\GitHubUpdateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public array $localVersion = [];
    public ?array $available = null;
    public string $loadingMessage = '';
    public ?string $errorMessage = null;
    public bool $checking = false;
    public bool $applying = false;

    /** @var array<string,bool> "v1.2.3|path/to/file" => true if selected */
    public array $selected = [];

    public ?array $diff = null;

    private function updater(): GitHubUpdateService
    {
        return app(GitHubUpdateService::class);
    }

    public function mount(): void
    {
        $this->refreshLocalVersion();
    }

    private function refreshLocalVersion(): void
    {
        $local = $this->updater()->getLocalVersion();

        $local['last_checked_at_formatted'] = !empty($local['last_checked_at'])
            ? Carbon::parse($local['last_checked_at'])->format('d.m.Y H:i')
            : null;

        if (!empty($local['update_history'])) {
            foreach ($local['update_history'] as &$history) {
                $history['date_formatted'] = Carbon::parse($history['date'])->format('d.m.Y H:i');
            }
        }

        $this->localVersion = $local;
    }

    public function check(): void
    {
        $this->errorMessage = null;
        $this->available = null;
        $this->checking = true;

        try {
            if (!config('gopanel.updater.enabled')) {
                $this->errorMessage = __('Yeniləmə sistemi deaktivdir');
                return;
            }

            $available = $this->updater()->getAvailableUpdates();
            if ($available === null) {
                $this->errorMessage = __('GitHub ilə əlaqə qurmaq mümkün olmadı');
                return;
            }

            $localCommit = $this->localVersion['installed_commit'] ?? null;

            foreach ($available['updates'] as &$update) {
                $update['files_status'] = $this->updater()->checkFileConflicts(
                    $update['files'],
                    $localCommit
                );
            }

            $local = $this->localVersion;
            $local['last_checked_at'] = now()->toIso8601String();
            $this->updater()->saveLocalVersion($local);
            $this->refreshLocalVersion();

            $this->available = $available;

            // Pre-select every non-conflict file.
            $this->selected = [];
            foreach ($available['updates'] as $update) {
                foreach ($update['files'] as $i => $file) {
                    $status = $update['files_status'][$i] ?? ['has_conflict' => false];
                    if (empty($status['has_conflict'])) {
                        $this->selected[$update['version'] . '|' . $file['path']] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->errorMessage = __('Xəta') . ': ' . $e->getMessage();
        } finally {
            $this->checking = false;
        }
    }

    public function toggleFile(string $key): void
    {
        if (!empty($this->selected[$key])) {
            unset($this->selected[$key]);
        } else {
            $this->selected[$key] = true;
        }
    }

    public function toggleAllForVersion(string $version, bool $checked): void
    {
        if (!$this->available) return;
        foreach ($this->available['updates'] as $update) {
            if ($update['version'] !== $version) continue;
            foreach ($update['files'] as $file) {
                $key = $version . '|' . $file['path'];
                if ($checked) {
                    $this->selected[$key] = true;
                } else {
                    unset($this->selected[$key]);
                }
            }
        }
    }

    public function selectedCount(): int
    {
        return count(array_filter($this->selected));
    }

    public function apply(): void
    {
        if ($this->selectedCount() === 0) {
            $this->errorMessage = __('Heç bir fayl seçilməyib');
            return;
        }
        if (!$this->available) return;

        $this->applying = true;
        $this->errorMessage = null;

        try {
            // Group selected files by version, pick latest version as target.
            $byVersion = [];
            foreach ($this->available['updates'] as $update) {
                foreach ($update['files'] as $file) {
                    $key = $update['version'] . '|' . $file['path'];
                    if (empty($this->selected[$key])) continue;
                    $byVersion[$update['version']][] = [
                        'path'   => $file['path'],
                        'action' => $file['action'],
                    ];
                }
            }
            ksort($byVersion, SORT_NATURAL);
            $targetVersion = array_key_last($byVersion);
            $allFiles = array_merge(...array_values($byVersion));

            $result = $this->updater()->applyFiles($allFiles, $targetVersion, [
                'admin_name'  => auth()->user()->name ?? 'Admin',
                'description' => '',
            ]);

            if (!empty($result['errors'])) {
                $this->errorMessage = count($result['errors']) . ' ' . __('faylda xəta baş verdi');
                $this->dispatch('notify', type: 'warning', message: $this->errorMessage);
                return;
            }

            try {
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                // ignore
            }

            $this->dispatch('notify', type: 'success', message: count($result['updated_files']) . ' ' . __('fayl uğurla yeniləndi'));
            $this->available = null;
            $this->selected = [];
            $this->refreshLocalVersion();
        } catch (\Throwable $e) {
            $this->errorMessage = __('Xəta') . ': ' . $e->getMessage();
        } finally {
            $this->applying = false;
        }
    }

    public function rollback(string $backupId): void
    {
        try {
            $result = $this->updater()->rollback($backupId);
            if (!$result['success']) {
                $this->dispatch('notify', type: 'error', message: $result['error'] ?? __('Geri alma xətası'));
                return;
            }

            try {
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
            }

            $this->dispatch('notify', type: 'success', message: count($result['restored_files']) . ' ' . __('fayl geri qaytarıldı'));
            $this->refreshLocalVersion();
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: __('Xəta') . ': ' . $e->getMessage());
        }
    }

    public function rollbackFile(string $backupId, string $path): void
    {
        try {
            $backupDir = config('gopanel.updater.backup_path');
            $backupFilePath = "{$backupDir}/{$backupId}/{$path}";

            if (!File::exists($backupFilePath)) {
                $this->dispatch('notify', type: 'error', message: __('Backup faylı tapılmadı'));
                return;
            }

            $targetPath = base_path($path);
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($backupFilePath, $targetPath);

            $this->dispatch('notify', type: 'success', message: $path . ' ' . __('uğurla geri qaytarıldı'));
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: __('Xəta') . ': ' . $e->getMessage());
        }
    }

    public function showDiff(string $path): void
    {
        try {
            $data = $this->updater()->getFileDiff($path);
            $this->diff = [
                'mode'    => 'live',
                'title'   => $path,
                'leftLabel'  => __('Lokal versiya'),
                'rightLabel' => __('Uzaq versiya (GitHub)'),
                'left'    => $this->renderDiffLines($data['local_content']  ?? '', $data['remote_content'] ?? '', 'local'),
                'right'   => $this->renderDiffLines($data['remote_content'] ?? '', $data['local_content']  ?? '', 'remote'),
            ];
        } catch (\Throwable $e) {
            $this->diff = ['title' => $path, 'leftLabel' => '', 'rightLabel' => '', 'left' => __('Xəta') . ': ' . $e->getMessage(), 'right' => ''];
        }
    }

    public function showHistoryDiff(string $backupId, string $path): void
    {
        try {
            $backupDir = config('gopanel.updater.backup_path');
            $backupFilePath = "{$backupDir}/{$backupId}/{$path}";
            $localPath = base_path($path);

            $backupContent  = File::exists($backupFilePath) ? File::get($backupFilePath) : null;
            $currentContent = File::exists($localPath) ? File::get($localPath) : null;

            $this->diff = [
                'mode'      => 'history',
                'title'     => $path . ' (' . __('Tarixçə') . ')',
                'leftLabel'  => __('Əvvəlki versiya (backup)'),
                'rightLabel' => __('Mövcud versiya'),
                'left'      => $this->renderDiffLines($backupContent ?? '', $currentContent ?? '', 'local'),
                'right'     => $this->renderDiffLines($currentContent ?? '', $backupContent ?? '', 'remote'),
            ];
        } catch (\Throwable $e) {
            $this->diff = ['title' => $path, 'leftLabel' => '', 'rightLabel' => '', 'left' => __('Xəta') . ': ' . $e->getMessage(), 'right' => ''];
        }
    }

    public function closeDiff(): void
    {
        $this->diff = null;
    }

    private function renderDiffLines(string $content, string $other, string $side): string
    {
        if ($content === '') {
            return '<span class="diff-line text-muted p-2">(' . __('Fayl mövcud deyil') . ')</span>';
        }
        $lines = explode("\n", $content);
        $otherLines = explode("\n", $other);
        $maxLen = max(count($lines), count($otherLines));
        $html = '';
        for ($i = 0; $i < $maxLen; $i++) {
            $line = $lines[$i] ?? null;
            $otherLine = $otherLines[$i] ?? null;
            $lineNum = $i + 1;

            if ($line === null) {
                $html .= '<span class="diff-line diff-removed"><span class="line-num"></span>&nbsp;</span>';
            } elseif ($otherLine === null) {
                $cls = $side === 'remote' ? 'diff-added' : 'diff-removed';
                $html .= '<span class="diff-line ' . $cls . '"><span class="line-num">' . $lineNum . '</span>' . e($line) . '</span>';
            } elseif ($line !== $otherLine) {
                $cls = $side === 'remote' ? 'diff-added' : 'diff-changed';
                $html .= '<span class="diff-line ' . $cls . '"><span class="line-num">' . $lineNum . '</span>' . e($line) . '</span>';
            } else {
                $html .= '<span class="diff-line"><span class="line-num">' . $lineNum . '</span>' . e($line) . '</span>';
            }
        }
        return $html;
    }
}; ?>

<div>
    @php $localVersion = $this->localVersion; @endphp

    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">{{ __('Sistem Yeniləmələri') }}</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        Gopanel
                                        <span class="badge bg-primary font-size-14">v{{ $localVersion['installed_version'] ?? '0.0.0' }}</span>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        @if ($localVersion['last_checked_at_formatted'] ?? null)
                                            {{ __('Son yoxlama') }}: {{ $localVersion['last_checked_at_formatted'] }}
                                        @else
                                            {{ __('Heç vaxt yoxlanmayıb') }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-primary" wire:click="check" wire:loading.attr="disabled" wire:target="check">
                                        <span wire:loading.remove wire:target="check"><i class="bx bx-refresh"></i> {{ __('Yeniləmələri yoxla') }}</span>
                                        <span wire:loading wire:target="check"><i class="bx bx-loader bx-spin"></i> {{ __('Yoxlanır...') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:loading wire:target="check" class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">{{ __('Yüklənir...') }}</span>
                            </div>
                            <p class="text-muted mt-3 mb-0">{{ __('GitHub ilə əlaqə qurulur...') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errorMessage)
                <div class="row">
                    <div class="col-xl-12">
                        <div class="alert alert-danger mb-3">{{ $errorMessage }}</div>
                    </div>
                </div>
            @endif

            @if ($available && empty($available['has_updates']))
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <div class="avatar-sm mx-auto mb-3">
                                    <span class="avatar-title rounded-circle bg-soft-success text-success font-size-24">
                                        <i class="bx bx-check"></i>
                                    </span>
                                </div>
                                <h5 class="text-success">{{ __('Sistem güncəldir!') }}</h5>
                                <p class="text-muted mb-0">{{ __('Heç bir yeniləmə tapılmadı.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($available && !empty($available['has_updates']))
                <div class="row">
                    <div class="col-xl-12">
                        @foreach ($available['updates'] as $updateIndex => $update)
                            @php
                                $allSelected = collect($update['files'])->every(fn ($f) => !empty($selected[$update['version'] . '|' . $f['path']]));
                            @endphp
                            <div class="card mb-3">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <span class="badge bg-info font-size-14">v{{ $update['version'] }}</span>
                                            <span class="ms-2">{{ $update['description'] }}</span>
                                        </h5>
                                        <small class="text-muted">{{ $update['date'] }}</small>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="select-all-{{ $updateIndex }}"
                                               wire:click="toggleAllForVersion('{{ $update['version'] }}', {{ $allSelected ? 'false' : 'true' }})"
                                               @checked($allSelected)>
                                        <label class="form-check-label" for="select-all-{{ $updateIndex }}">{{ __('Hamısını seç') }}</label>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40px;"></th>
                                                    <th>{{ __('Fayl') }}</th>
                                                    <th style="width: 120px;">{{ __('Əməliyyat') }}</th>
                                                    <th style="width: 130px;">Status</th>
                                                    <th style="width: 100px;">{{ __('Hərəkət') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($update['files'] as $fileIndex => $file)
                                                    @php
                                                        $status = $update['files_status'][$fileIndex] ?? ['status' => 'safe', 'has_conflict' => false];
                                                        $key = $update['version'] . '|' . $file['path'];
                                                        $isConflict = !empty($status['has_conflict']);
                                                    @endphp
                                                    <tr class="{{ $isConflict ? 'table-warning' : '' }}">
                                                        <td class="text-center">
                                                            <input class="form-check-input" type="checkbox"
                                                                   wire:click="toggleFile('{{ $key }}')"
                                                                   @checked(!empty($selected[$key]))>
                                                        </td>
                                                        <td>
                                                            <code class="text-dark" style="font-size: 12px;">{{ $file['path'] }}</code>
                                                            @if ($isConflict)
                                                                <br><small class="text-warning"><i class="bx bx-error"></i> {{ __('Bu fayl lokal olaraq da dəyişdirilib') }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @switch($file['action'])
                                                                @case('added')    <span class="badge bg-success">{{ __('Yeni') }}</span> @break
                                                                @case('modified') <span class="badge bg-primary">{{ __('Dəyişmiş') }}</span> @break
                                                                @case('deleted')  <span class="badge bg-danger">{{ __('Silinmiş') }}</span> @break
                                                                @default <span class="badge bg-secondary">{{ $file['action'] }}</span>
                                                            @endswitch
                                                        </td>
                                                        <td>
                                                            @switch($status['status'] ?? 'safe')
                                                                @case('safe')     <span class="badge bg-soft-success text-success">✅ {{ __('Təhlükəsiz') }}</span> @break
                                                                @case('conflict') <span class="badge bg-soft-warning text-warning">⚠️ {{ __('Konflikt') }}</span> @break
                                                                @case('new')      <span class="badge bg-soft-info text-info">🆕 {{ __('Yeni fayl') }}</span> @break
                                                                @case('delete')   <span class="badge bg-soft-danger text-danger">🗑️ {{ __('Silinəcək') }}</span> @break
                                                                @default <span class="badge bg-secondary">{{ __('Bilinmir') }}</span>
                                                            @endswitch
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" wire:click="showDiff('{{ $file['path'] }}')" title="{{ __('Diff bax') }}">
                                                                <i class="bx bx-git-compare"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted">{{ $this->selectedCount() }} {{ __('fayl seçilib') }}</span>
                                    <button type="button" class="btn btn-success"
                                            wire:click="apply"
                                            wire:confirm="{{ __('Seçilmiş faylları yeniləmək istədiyinizə əminsiniz?') }}"
                                            wire:loading.attr="disabled" wire:target="apply">
                                        <span wire:loading.remove wire:target="apply"><i class="bx bx-download"></i> {{ __('Seçilmişləri yenilə') }}</span>
                                        <span wire:loading wire:target="apply"><i class="bx bx-loader bx-spin"></i> {{ __('Yenilənir...') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (!empty($localVersion['update_history']))
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ __('Yeniləmə tarixçəsi') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Versiya') }}</th>
                                                <th>{{ __('Tarix') }}</th>
                                                <th>{{ __('Fayl sayı') }}</th>
                                                <th>{{ __('Qeyd') }}</th>
                                                <th>{{ __('Əməliyyat') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (array_reverse($localVersion['update_history']) as $hIndex => $history)
                                                <tr>
                                                    <td><span class="badge bg-info">v{{ $history['version'] }}</span></td>
                                                    <td>
                                                        {{ $history['date_formatted'] }}
                                                        @if (!empty($history['applied_by']))
                                                            <br><small class="text-muted"><i class="bx bx-user"></i> {{ $history['applied_by'] }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $history['files'] }} {{ __('fayl') }}</td>
                                                    <td>
                                                        @if (!empty($history['description']))
                                                            <small class="text-muted">{{ $history['description'] }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-nowrap" x-data="{ open: false }">
                                                        @if (!empty($history['file_details']))
                                                            <button type="button" class="btn btn-outline-info me-1" x-on:click="open = !open" title="{{ __('Dəyişdirilmiş faylların siyahısını göstər') }}">
                                                                <i class="bx bx-show"></i>
                                                            </button>
                                                        @endif
                                                        <button type="button" class="btn btn-outline-warning"
                                                                wire:click="rollback('{{ $history['backup_id'] }}')"
                                                                wire:confirm="{{ __('Bütün faylları əvvəlki vəziyyətinə qaytarmaq istədiyinizə əminsiniz?') }}"
                                                                title="{{ __('Bütün faylları əvvəlki vəziyyətinə qaytar') }}">
                                                            <i class="bx bx-undo"></i> {{ __('Geri al') }}
                                                        </button>

                                                        @if (!empty($history['file_details']))
                                                            <div x-show="open" x-cloak class="mt-2 text-start">
                                                                <table class="table table-sm mb-0" style="font-size: 12px;">
                                                                    @foreach ($history['file_details'] as $fd)
                                                                        <tr>
                                                                            <td class="border-0" style="width: 70px;">
                                                                                @switch($fd['action'])
                                                                                    @case('added')   <span class="badge bg-success">{{ __('Yeni') }}</span> @break
                                                                                    @case('deleted') <span class="badge bg-danger">{{ __('Silinib') }}</span> @break
                                                                                    @default <span class="badge bg-primary">{{ __('Dəyişib') }}</span>
                                                                                @endswitch
                                                                            </td>
                                                                            <td class="border-0">
                                                                                <code>{{ $fd['path'] }}</code>
                                                                            </td>
                                                                            <td class="border-0 text-end text-nowrap" style="width: 100px;">
                                                                                <button class="btn btn-outline-secondary py-0 px-1"
                                                                                        wire:click="showHistoryDiff('{{ $history['backup_id'] }}', '{{ $fd['path'] }}')"
                                                                                        title="{{ __('Əvvəlki və mövcud versiya arasındakı fərqi göstər') }}">
                                                                                    <i class="bx bx-git-compare"></i>
                                                                                </button>
                                                                                <button class="btn btn-outline-warning py-0 px-1 ms-1"
                                                                                        wire:click="rollbackFile('{{ $history['backup_id'] }}', '{{ $fd['path'] }}')"
                                                                                        wire:confirm="{{ __('Bu faylı backup-dan geri qaytarmaq istəyirsiniz?') }}"
                                                                                        title="{{ __('Bu faylı backup-dan geri qaytar') }}">
                                                                                    <i class="bx bx-undo"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @if ($diff)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $diff['title'] }}</h5>
                        <button type="button" class="btn-close" wire:click="closeDiff" aria-label="{{ __('Bağla') }}"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="row g-0">
                            <div class="col-md-6 border-end">
                                <div class="p-2 bg-light border-bottom">
                                    <strong class="text-danger">{{ $diff['leftLabel'] }}</strong>
                                </div>
                                <pre class="p-3 mb-0 diff-pre">{!! $diff['left'] !!}</pre>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 bg-light border-bottom">
                                    <strong class="text-success">{{ $diff['rightLabel'] }}</strong>
                                </div>
                                <pre class="p-3 mb-0 diff-pre">{!! $diff['right'] !!}</pre>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDiff">{{ __('Bağla') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
