<?php

namespace App\Http\Controllers\Gopanel\Translations;

use App\Http\Controllers\GoPanelController;
use App\Models\Geography\Country;
use App\Models\Geography\Language;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class LanguageController extends GoPanelController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $languagesList = Language::orderBy('sort_order', 'ASC')
            ->get();

        return view('gopanel.pages.settings.languages.index', compact('languagesList'));
    }

    public function getForm(Language $item, Request $request)
    {
        try {
            $route = route('gopanel.settings.languages.save', $item);
            $this->response['html'] = View::make('gopanel.pages.settings.languages.partials.form', [
                'item' => $item,
                'route' => $route,
                'countries' => Country::all(),
            ])->render();
            $this->success_response([], 'Form yaradildi');
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function save(Language $item, Request $request)
    {
        try {
            $data = $request->except(['_token']);
            $data['default'] = (bool) ($data['default'] ?? false);
            $data['is_active'] = (bool) ($data['is_active'] ?? false);

            if ($data['default']) {
                $data['is_active'] = true;
            }

            $message = !is_null($item->id)
                ? 'Melumat ugurla deyisdirildi!'
                : 'Melumat ugurla yaradildi!';

            $item = $this->crudHelper->saveInstance($item, $data);

            Language::ensureSingleDefault($item);
            Language::ensureFallbackDefault();

            $this->success_response($item->fresh(), $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function toggleDefault(Request $request)
    {
        try {
            if (!$request->has('id') || !$request->has('status')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $item = Language::query()
                ->where(is_numeric($request->id) ? 'id' : 'uid', $request->id)
                ->first();

            if (!$item) {
                throw new Exception('Gonderilen id-e uygun dil tapilmadi');
            }

            $makeDefault = $request->status === 'true';

            DB::transaction(function () use ($item, $makeDefault) {
                if ($makeDefault) {
                    Language::query()->update(['default' => false]);

                    $item->forceFill([
                        'default' => true,
                        'is_active' => true,
                    ])->save();
                } else {
                    $item->forceFill([
                        'default' => false,
                    ])->save();

                    Language::ensureFallbackDefault();
                }
            });

            $this->success_response($item->fresh(), 'Melumat ugurla deyisdirildi');
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
