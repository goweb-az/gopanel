{{-- Saytın maşın faylları - siyahı `App\Services\Seo\MachineFilesService::links()`-dən hazır gəlir --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Maşın faylları</h5>
        <p class="text-muted mb-0 mt-1">
            Axtarış sistemləri və AI agentləri üçün hazır fayllar. İstənilən səhifənin ünvanına
            <code>.md</code> əlavə etsəniz, onun Markdown variantı açılır.
        </p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fayl</th>
                        <th>Təyinat</th>
                        <th>Mənbə</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($machineFiles ?? [] as $file)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ $file['url'] }}" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt me-1"></i>{{ $file['title'] }}
                                </a>
                            </td>
                            <td>{{ $file['description'] }}</td>
                            <td class="text-muted">{{ $file['source'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Maşın faylı tapılmadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
