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
}
