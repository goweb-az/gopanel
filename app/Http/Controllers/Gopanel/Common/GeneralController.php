<?php

namespace App\Http\Controllers\Gopanel\Common;

use App\Helpers\Common\ModelList;
use App\Http\Controllers\Controller;
use App\Services\GeneralService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class GeneralController extends Controller
{
    private GeneralService $service;

    public function __construct(GeneralService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    private function resolveItem($modelInstance, $id)
    {
        $column = is_numeric($id) && (int) $id == $id ? 'id' : 'uid';

        return $modelInstance->where($column, $id)->first();
    }

    public function add(Request $request)
    {
        try {
            if (!$request->has('key')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $morph = $request->key;
            $hash = $request->hash;
            $class = $this->service->getMorphClass($morph, $hash);
            $this->response['request'] = $request->all();
            $attributes = $request->except(['key', 'hash']);

            if (!class_exists($class)) {
                throw new Exception("Bele bir key movcud deyil ({$morph})");
            }

            $modelInstance = app($class);
            $item = new $modelInstance();

            foreach ($attributes as $key => $value) {
                $item->$key = $value;
            }

            if ($item->save()) {
                $this->success_response($item, 'Melumat ugurla elave edildi');
            } else {
                $this->response['data'] = $item;
                $this->response['message'] = 'Melumat elave edile bilmedi';
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function edit(Request $request)
    {
        try {
            if (!$request->has('id') || !$request->has('key')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $morph = $request->key;
            $id = $request->id;
            $hash = $request->hash;
            $class = $this->service->getMorphClass($morph, $hash);
            $this->response['request'] = $request->all();
            $attributes = $request->except(['id', 'key']);

            if (!class_exists($class)) {
                throw new Exception("Bele bir model movcud deyil ({$class})");
            }

            $modelInstance = app($class);
            $item = $this->resolveItem($modelInstance, $id);

            if (!isset($item->id)) {
                $this->response['data'] = $item;
                $this->response['message'] = 'Gonderilen id-e uygun hec bir melumat tapilmadi';
                return $this->response_json();
            }

            foreach ($attributes as $key => $value) {
                if (isset($item->$key)) {
                    $item->$key = $value;
                }
            }

            if ($item->save()) {
                $this->success_response($item, 'Melumat ugurla deyisdirildi');
            } else {
                $this->response['data'] = $item;
                $this->response['message'] = 'Melumat deyisdirile bilmedi';
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function delete(Request $request, $id = null)
    {
        try {
            if (is_null($id)) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $class = ModelList::get($request->key) ?? $request->key;
            $hard = $request->has('hard') && $request->hard == 'true';

            if (!class_exists($class)) {
                throw new Exception("Bele bir model movcud deyil ({$class})");
            }

            $modelInstance = app($class);
            $column = is_numeric($id) && (int) $id == $id ? 'id' : 'uid';
            $item = $modelInstance->where($column, $id)->first();

            if (!isset($item->id)) {
                $this->response['data'] = $item;
                $this->response['message'] = 'Gonderilen id-e uygun hec bir melumat tapilmadi';
                return $this->response_json();
            }

            if ($hard) {
                if ($item->forceDelete()) {
                    $this->success_response($item, 'Melumat ugurla silindi');
                } else {
                    $this->response['message'] = 'Melumat siline bilmedi';
                }
            } else {
                $this->response['hard'] = true;
                if ($item->delete()) {
                    $this->success_response($item, 'Melumat ugurla silindi');
                } else {
                    $this->response['message'] = 'Melumat ugurla silindi';
                }
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function editable($id, Request $request)
    {
        $this->response['request'] = $request->all();

        try {
            if (!$request->has('id') || !$request->has('model')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $class = '\\' . str_replace('\\\\', '\\', $request->model);
            $id = $request->id;
            $row = $request->row;

            if (!class_exists($class)) {
                throw new Exception("Bele bir model movcud deyil ({$class})");
            }

            $modelInstance = app($class);
            $item = $this->resolveItem($modelInstance, $id);
            $item->$row = $request->value;

            if (!$item->save()) {
                throw new Exception('Xeta bash verdi');
            }

            $this->success_response($item, 'Melumat ugurla deyisdirildi');
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function statusChange(Request $request)
    {
        try {
            if (!$request->has('status') || !$request->has('id')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $status = $request->status;
            $class = $request->model;
            $id = $request->id;
            $row = $request->row;

            if (!$this->service->class_exists($class)) {
                throw new Exception("Bele bir model movcud deyil ({$class})");
            }

            $modelInstance = $this->service->modelInstance($class);
            $item = $this->resolveItem($modelInstance, $id);

            if (!isset($item->id) || !isset($item->$row)) {
                $this->response['data'] = $item;
                $this->response['message'] = 'Gonderilen id-e uygun hec bir melumat tapilmadi';
                return $this->response_json();
            }

            $item->$row = $status == 'true' ? 1 : 0;

            if ($item->save()) {
                $this->success_response($item->fresh(), 'Melumat ugurla deyisdirildi');
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function sortable(Request $request)
    {
        try {
            $row = $request->row;
            $rows = $request->data;
            $requestModel = ModelList::get($request->key) ?? $request->key;
            $counter = 0;
            $updatedData = [];

            if (!class_exists($requestModel)) {
                throw new Exception("Bele bir model movcud deyil ({$requestModel})");
            }

            $modelInstance = app($requestModel);
            parse_str(urldecode($rows), $rows);

            if (!isset($rows['item']) || !count($rows['item'])) {
                throw new Exception('Gonderilen melumat tapilmadi');
            }

            foreach ($rows['item'] as $key => $value) {
                $item = $modelInstance->find($value);
                $item->$row = $key;
                if ($item->save()) {
                    $counter++;
                    $updatedData[] = $item;
                }
            }

            if (count($rows['item']) == $counter) {
                $this->success_response($item, 'Melumat ugurla deyisdirildi');
            } elseif ($counter) {
                $this->success_response($item, 'Butun melumatlar deyisdirile bilmedi');
            } else {
                throw new Exception('Yenilenme zamani xeta bash verdi');
            }

            $this->response['requestedData'] = $rows['item'];
            $this->response['updatedData'] = $updatedData;
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function archive(Request $request)
    {
        try {
            if (!$request->has('id')) {
                throw new Exception('Melumatlar duzgun gonderilmeyib');
            }

            $class = $request->key;
            $id = $request->id;
            $hash = $request->hash;
            $class = $this->service->getMorphClass($class, $hash);

            if (!class_exists($class)) {
                throw new Exception("Bele bir model movcud deyil ({$class})");
            }

            $modelInstance = app($class);
            $item = $this->resolveItem($modelInstance, $id);

            if (!isset($item->id)) {
                $this->response['data'] = $item;
                $this->response['message'] = 'Gonderilen id-e uygun hec bir melumat tapilmadi';
                return $this->response_json();
            }

            $item->archived_at = now();
            if ($item->save()) {
                $this->success_response($item, 'Melumat ugurla arxivlendi');
            } else {
                $this->response['message'] = 'Melumat arxivlenen zaman xeta bash verdi';
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function clearCache(Request $request)
    {
        try {
            $type = $request->input('type', 'basic');

            switch ($type) {
                case 'basic':
                    Cache::clear();
                    Cache::flush();
                    Artisan::call('cache:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'all':
                    Cache::flush();
                    Artisan::call('optimize:clear');
                    break;
                default:
                    throw new Exception('Namelum cache temizleme tipi');
            }

            $this->success_response([], "Cache temizlendi: <strong>{$type}</strong><br>" . (Artisan::output() ?? ''));
        } catch (Exception $e) {
            $this->response['message'] = $e->getMessage();
        }

        return $this->response_json();
    }

    public function route()
    {
        return response()->json($this->service->getSharedRoutes(), 200);
    }
}
