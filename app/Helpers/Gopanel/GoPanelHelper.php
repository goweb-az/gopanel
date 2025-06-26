<?php


namespace App\Helpers\Gopanel;

use Exception;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Illuminate\Support\Str;

class GoPanelHelper
{
    public static function save(Model $item, $data): Model
    {
        return (new static())->saveInstance($item, $data);
    }

    public function saveInstance(Model $item, $data): Model
    {
        if (is_null($item))
            throw new InvalidArgumentException('Item cannot be null.');

        foreach ($data as $key => $value) {
            if (!is_array($value))
                $item->$key = $value;
        }
        $item->save();
        return $item->fresh();
    }


    public function upload_public($file, $folder = 'other', $filename = null)
    {
        if (!$file->isValid())
            throw new Exception("Fayl formatı düzgün deyil");
        $extension      = $file->getClientOriginalExtension();
        if (!$filename) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . "-" . uniqid();
        }
        $filename = Str::slug($filename) . '.' . $extension;


        $path = "site/{$folder}/{$filename}";

        $targetPath = public_path("site/{$folder}");
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $file->move(public_path("site/{$folder}"), $filename);
        return $path;
    }


    public function getFile($path)
    {
        if (file_exists(public_path($path))) {
            return url($path);
        }
        return $path;
    }


    public function is_active_btn(Model $item, $row = 'is_active', $checked = false, $class = [], $url = null, $active_text = 'Aktiv', $deactive_text = 'Deaktiv')
    {
        $class      = count($class) ? implode(" ", $class) : 'is_active';
        $model      = get_class($item);
        $checked    = $checked ? 'checked' : '';
        $url        = is_null($url) ? route("gopanel.general.status.change") : $url;
        return '
            <input
                  class="' . $class . '"
                  type="checkbox"
                  data-toggle="switchbutton"
                  data-onlabel="' . $active_text . '"
                  data-offlabel="' . $deactive_text . '"
                  data-onstyle="success"
                  data-offstyle="danger"
                  data-id="' . $item->id . '"
                  data-row="' . $row . '"
                  data-model="' . $model . '"
                  data-url="' . $url . '"
                  data-size="sm"
                  ' . $checked . '
            />
        ';
    }


    public function edit_btn(Model $item, $url = null, $redirect = false): string
    {
        $url        = is_null($url) ? route("gopanel.general.edit") : $url;
        $editClass  = $redirect ? 'redirect_manage' : 'edit';
        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light ' . $editClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    public function delete_btn(Model $item, $url = null)
    {
        $url        = is_null($url) ? route("gopanel.general.delete", $item) : $url;
        return '<a  class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item)  . '"" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }



    public function upload($file, $folder = 'other', $filename = null)
    {
        if (!$file->isValid())
            throw new Exception("Fayl formatı düzgün deyil");
        $extension      = $file->getClientOriginalExtension();
        if (!$filename) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . "-" . uniqid();
        }
        $filename = Str::slug($filename) . '.' . $extension;


        $path = "site/{$folder}/{$filename}";


        $file->move(public_path("site/{$folder}"), $filename);
        return $path;
    }



    public function getYoutuebId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? NULL;
    }


    public function file_name_genarte(array $request)
    {
        $name = uniqid();
        if (isset($request['title']['az'])) {
            $name = Str::slug($request['title']['az'] ?? uniqid(), '-', 'az') . "-" . uniqid();
        }
        if (isset($request['name']['az'])) {
            $name = Str::slug($request['name']['az'] ?? uniqid(), '-', 'az') . "-" . uniqid();
        }
        return $name;
    }
}
