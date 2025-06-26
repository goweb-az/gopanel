<?php

namespace App\Http\Controllers\Gopanel\Common;

use App\Enums\Gopanel\ModelList;
use App\Http\Controllers\Controller;
use App\Services\GeneralService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class GeneralController extends Controller
{


    private GeneralService $service;

    public function __construct(GeneralService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function add(Request $request)
    {
        try {
            if ($request->has("key")) {
                $morph          = $request->key;
                $hash           = $request->hash;
                $hash           = $request->hash;
                $class          = $this->service->getMorphClass($morph, $hash);
                $this->response['request'] = $request->all();
                $attributes = $request->except(['key', 'hash']);
                if (class_exists($class)) {
                    $modelInstance = app($class);
                    $item = new $modelInstance();

                    foreach ($attributes as $key => $value) {
                        $item->$key = $value;
                    }
                    if ($item->save()) {
                        $this->success_response($item, "Məlumat uğurla əlavə edildi");
                    } else {
                        $this->response['data'] = $item;
                        $this->response['message'] = 'Məlumat əlavə edilə bilmədi';
                    }
                } else {
                    throw new Exception("Belə bir key mövcud deyil ({$morph})");
                }
            } else {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function edit(Request $request)
    {
        try {
            if ($request->has("id") && $request->has("key")) {
                $morph          = $request->key;
                $id            = $request->id;
                $hash           = $request->hash;
                $class          = $this->service->getMorphClass($morph, $hash);
                $this->response['request']      = $request->all();
                $attributes                     = $request->except(['id', 'key']);
                if (class_exists($class)) {
                    $modelInstance = app($class);
                    $item = $modelInstance->where("id", $id)->first();
                    if (isset($item->id)) {
                        foreach ($attributes as $key => $value) {
                            if (isset($item->$key)) {
                                $item->$key = $value;
                            }
                        }
                        if ($item->save()) {
                            $this->success_response($item, "Məlumat uğurla dəyişdirildi");
                        } else {
                            $this->response['data']       = $item;
                            $this->response['message']    = 'Məlumat dəyişdirilə bilmədi';
                        }
                    } else {
                        $this->response['data'] = $item;
                        $this->response['message'] = 'Göndərilən "id" yə uyqun heç bir məlumat tapılmadı!!!';
                    }
                } else {
                    throw new Exception("Belə bir model mövcud deyil ({$class})");
                }
            } else {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function delete($id = null, Request $request)
    {
        try {
            if (is_null($id)) {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
            $class  = ModelList::get($request->key)->value ?? $request->key;
            $hard   = $request->has("hard") && $request->hard == 'true';

            if (class_exists($class)) {
                $modelInstance = app($class);
                $column = is_numeric($id) && (int)$id == $id ? 'id' : 'uid';
                $item = $modelInstance->where($column, $id)->first();
                if (isset($item->id)) {
                    if ($hard) {
                        if ($item->forceDelete()) {
                            $this->success_response($item, "Məlumat uğurla silindi");
                        } else {
                            $this->response['message'] = 'Məlumat silinə bilmədi';
                        }
                    } else {
                        $this->response['hard'] = true;
                        if ($item->delete()) {
                            $this->success_response($item, "Məlumat uğurla silindi");
                        } else {
                            $this->response['message'] = 'Məlumat uğurla silindi';
                        }
                    }
                } else {
                    $this->response['data'] = $item;
                    $this->response['message'] = 'Göndərilən "id" yə uyqun heç bir məlumat tapılmadı!!!';
                }
            } else {
                throw new Exception("Belə bir model mövcud deyil ({$class})");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function editable($id, Request $request)
    {
        $this->response['request']      = $request->all();
        try {
            if ($request->has("id") && $request->has("model")) {
                $class          = $request->model;
                $id             = $request->id;
                $row            = $request->row;
                $class          = str_replace('\\\\', '\\', $class);
                $class          = '\\' . $class;
                if (class_exists($class)) {
                    $modelInstance = app($class);
                    $item = $modelInstance->where("id", $id)->first();
                    $item->$row = $request->value;
                    if (!$item->save())
                        throw new Exception("Xeta bash verdi");
                    $this->success_response($item, "Məlumat uğurla dəyişdirildi");
                } else {
                    throw new Exception("Belə bir model mövcud deyil ({$class})");
                }
            } else {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function statusChange(Request $request)
    {
        try {
            if ($request->has("status") && $request->has("id")) {
                $status     = $request->status;
                $class      = $request->model;
                $id         = $request->id;
                $row        = $request->row;
                if ($this->service->class_exists($class)) {
                    $modelInstance = $this->service->modelInstance($class);

                    $item = $modelInstance->where("id", $id)->first();
                    if (isset($item->id) && isset($item->$row)) {
                        $item->$row = $status == 'true' ? 1 : 0;
                        if ($item->save()) {
                            $this->success_response($item, "Məlumat uğurla dəyişdirildi");
                        }
                    } else {
                        $this->response['data']       = $item;
                        $this->response['message'] = 'Göndərilən "id" yə uyqun heç bir məlumat tapılmadı!!!';
                    }
                } else {
                    throw new Exception("Belə bir model mövcud deyil ({$class}) ");
                }
            } else {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function sortable(Request $request)
    {
        try {
            $row            = $request->row;
            $rows           = $request->data;
            $requestModel   = $request->key;
            $counter        = 0;
            $updatedData    = [];
            if (class_exists($requestModel)) {
                $modelInstance = app($requestModel);
                parse_str(urldecode($rows), $rows);
                if (isset($rows['item']) && count($rows['item'])) {
                    foreach ($rows['item'] as $key => $value) {
                        $item = $modelInstance->find($value);
                        $item->$row = $key;
                        if ($item->save()) {
                            $counter++;
                            $updatedData[] = $item;
                        }
                    }
                    if (count($rows['item']) == $counter) {
                        $this->success_response($item, "Məlumat uğurla dəyişdirildi");
                    } else if ($counter) {
                        $this->success_response($item, "Bütün məlumatlar dəyişdirilə bilmədi !!!");
                    } else {
                        throw new Exception("Yenilənmə zamanı xəta baş verdi !!!");
                    }
                    $this->response['requestedData'] = $rows['item'];
                    $this->response['updatedData']   = $updatedData;
                } else {
                    throw new Exception("Göndərilən məlumat tapılmadı!!!");
                }
            } else {
                throw new Exception("Belə bir model mövcud deyil ({$requestModel}) ");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function archive(Request $request)
    {
        try {
            if ($request->has("id")) {
                $class  = $request->key;
                $id    = $request->id;
                $hash   = $request->hash;
                $class  = $this->service->getMorphClass($class, $hash);
                if (class_exists($class)) {
                    $modelInstance = app($class);
                    $item = $modelInstance->where("id", $id)->first();
                    if (isset($item->id)) {
                        $item->archived_at = now();
                        if ($item->save()) {
                            $this->success_response($item, "Məlumat uğurla Arxivləndi");
                        } else {
                            $this->response['message'] = 'Məlumat Arxivlənən zaman xəta baş verdi!';
                        }
                    } else {
                        $this->response['data'] = $item;
                        $this->response['message'] = 'Göndərilən "id" yə uyqun heç bir məlumat tapılmadı!!!';
                    }
                } else {
                    throw new Exception("Belə bir model mövcud deyil ({$class})");
                }
            } else {
                throw new Exception("Məlumatlar düzgün göndərilməyib");
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
                    throw new Exception("Naməlum cache təmizləmə tipi");
            }
            $this->success_response([], "Cache təmizləndi: <strong> {$type} </strong><br>" . (Artisan::output() ?? ''));
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
