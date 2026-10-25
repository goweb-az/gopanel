<?php

namespace App\Http\Controllers\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Translations\ExportTranslationsRequest;
use App\Http\Requests\Gopanel\Translations\StoreTranslationRequest;
use App\Http\Requests\Gopanel\Translations\TranslationExcelImportRequest;
use App\Models\Translations\Translation;
use App\Services\Gopanel\Translations\TranslationCacheService;
use App\Services\Gopanel\Translations\TranslationExcelService;
use App\Services\Gopanel\Translations\TranslationExportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request, TranslationPageRegistry $pages)
    {
        $locale      = $request->has("locale") ? $request->input("locale") : app()->getLocale();
        $filters     = $request->only('locale', 'platform', 'page');
        $allPages    = $pages->all();
        $hasFilters  = !empty($filters['locale']) || !empty($filters['platform']) || !empty($filters['page']);
        $filterPages = !empty($filters['platform']) ? ($allPages[$filters['platform']] ?? []) : [];
        // Excel ixracı cari filtrə (platforma/səhifə) uyğun gəlsin
        $excelExportUrl = route('gopanel.settings.translations.excel.export', array_filter([
            'platform' => $filters['platform'] ?? null,
            'page'     => $filters['page'] ?? null,
        ]));

        return view("gopanel.pages.settings.translations.index", compact(
            'locale', 'filters', 'allPages', 'hasFilters', 'filterPages', 'excelExportUrl'
        ));
    }




    public function getForm(Translation $item, Request $request, TranslationPageRegistry $pages)
    {
        try {
            $route = route("gopanel.settings.translations.save.form", $item);
            $this->response['html'] = View::make('gopanel.pages.settings.translations.partials.form', [
                'item'              => $item,
                'route'             => $route,
                'platforms'         => TranslationPlatfroms::cases(),
                'groups'            => TranslationGroups::cases(),
                'allPages'          => $pages->all(),
                'selectedExists'    => false
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Translation $item, StoreTranslationRequest $request, TranslationCacheService $cache)
    {
        try {
            $data = $request->validated();
            if (isset($item->id)) {
                $message    = "Məlumat uğurla dəyişdirildi!";
            } else {
                $item       = new Translation();
                $message    = "Məlumat uğurla yaradıldı!";
            }
            foreach (($data['value'] ?? []) as $locale => $value) {
                $this->saveData($item, $data, $locale, $value);
            }

            $cache->forgetLocales(array_keys($data['value'] ?? []));

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }



    private function saveData($item, $data, $locale, $value)
    {
        $key      = $data['key'] ?? $item->key;
        $platform = $data['platform'] ?? $item->platform;
        $page     = $data['page'] ?? ($item->page ?: 'general');
        $filename = $data['filename'] ?? ($item->filename ?: $platform);
        $group    = $data['group'] ?? $item->group;

        $existingItem = Translation::where('key', $key)
            ->where('platform', $platform)
            ->where('page', $page)
            ->where('locale', $locale)
            ->first();

        if ($existingItem) {
            $existingItem->value    = $value;
            $existingItem->key      = $key;
            $existingItem->platform = $platform;
            $existingItem->page     = $page;
            $existingItem->filename = $filename;
            $existingItem->group    = $group;
            $existingItem->save();
        } else {
            $newItem = new Translation();
            $newItem->key       = $key;
            $newItem->platform  = $platform;
            $newItem->page      = $page;
            $newItem->filename  = $filename;
            $newItem->group     = $group;
            $newItem->locale    = $locale;
            $newItem->value     = $value;
            $newItem->save();
        }
    }


    /**
     * Tərcümələri Excel-ə çıxarır (cari platforma/səhifə filtrinə görə).
     * Fayl heç bir dəyişiklik etmədən `excelImport()`-a geri yüklənə bilər.
     */
    public function excelExport(ExportTranslationsRequest $request, TranslationExcelService $service): StreamedResponse
    {
        $data     = $request->validated();
        $platform = $data['platform'] ?? null;
        $page     = $data['page'] ?? null;
        $writer   = new XlsxWriter($service->export($platform, $page));
        $name     = 'translations-' . implode('-', array_filter([$platform ?: 'all', $page])) . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(fn () => $writer->save('php://output'), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function excelImport(TranslationExcelImportRequest $request, TranslationExcelService $service): JsonResponse
    {
        try {
            $result = $service->import($request->file('file'), $request->overwrite());

            $this->response['data'] = $result;

            if ($result['created'] === 0 && $result['updated'] === 0 && $result['failed'] > 0) {
                $this->response['message'] = 'Heç bir sətir yazılmadı - xətalara baxın.';
                return $this->response_json();
            }

            $this->success_response($result, 'İdxal tamamlandı.');
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function exportJson(ExportTranslationsRequest $request, TranslationExportService $service): JsonResponse
    {
        try {
            $result = $service->export($request->validated());
            $this->success_response(
                $result,
                "{$result['file_count']} JSON fayl yaradıldı ({$result['row_count']} sətir)."
            );
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

}
