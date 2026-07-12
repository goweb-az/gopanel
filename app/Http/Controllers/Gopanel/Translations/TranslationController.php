<?php

namespace App\Http\Controllers\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Translations\BulkTranslationImportRequest;
use App\Http\Requests\Gopanel\Translations\ExportTranslationsRequest;
use App\Http\Requests\Gopanel\Translations\StoreTranslationRequest;
use App\Models\Translations\Translation;
use App\Services\Gopanel\Translations\TranslationBulkImportService;
use App\Services\Gopanel\Translations\TranslationCacheService;
use App\Services\Gopanel\Translations\TranslationExportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class TranslationController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request, TranslationPageRegistry $pages)
    {
        $items      = Translation::all();
        $locale     = $request->has("locale") ? $request->input("locale") : app()->getLocale();
        $filters    = $request->only('locale', 'platform', 'page');
        $allPages   = $pages->all();

        return view("gopanel.pages.settings.translations.index", compact("items", 'locale', 'filters', 'allPages'));
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


    public function bulkImport(BulkTranslationImportRequest $request, TranslationBulkImportService $service): JsonResponse
    {
        try {
            $result = $service->import($request->validated(), $request->file('file'));

            $failedOnly = $result['created'] === 0
                && $result['updated'] === 0
                && $result['failed'] > 0;

            if ($failedOnly) {
                $this->response['data']    = $result;
                $this->response['message'] = 'İdxal zamanı heç bir sətir yazılmadı.';
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


    public function jsonTemplate()
    {
        $content = json_encode(
            ['example_key' => 'Example value'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return response($content, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="translations-template.json"',
        ]);
    }


    public function xlsxTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'key');
        $sheet->setCellValue('B1', 'value');
        $sheet->setCellValue('A2', 'example_key');
        $sheet->setCellValue('B2', 'Example value');

        $writer = new XlsxWriter($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'translations-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
