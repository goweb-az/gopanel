<?php

namespace App\Services\Site;


use App\Models\Geography\Language;
use App\Services\Activity\LogService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Str;


class SiteService
{

    public $logging;

    public function __construct()
    {
        $this->logging = new LogService("gopanel-site");
        // $this->logging->info("Start site service");
    }


    public function saveModel(Model $item, $data): Model
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

    public function share(array $data)
    {
        return view()->share($data);
    }


    public function createTranslations($item, Request $request)
    {
        $this->logging->info("start createTranslations", [
            'item'      => $item,
            'request'   => $request->all()
        ]);

        try {
            foreach (Language::all() as $lang) {
                foreach ($item->translatedAttributes as $key => $transAttribute) {

                    $newValue = $request?->$transAttribute[$lang->code] ?? null;
                    if (isset($item->slug_key) && $transAttribute == 'slug' && in_array($item?->slug_key, $item->translatedAttributes)) {
                        $titleKey = $item?->slug_key;
                        $titleValue = $request?->$titleKey[$lang->code] ?? null;
                        $newValue = null;
                        if ($titleValue)
                            $newValue = Str::slug($titleValue);
                    }
                    $translation = $item->translations()->updateOrCreate(
                        ['locale' => $lang->code, 'key' => $transAttribute],
                        ['value' => $newValue]
                    );

                    // Log mesajı
                    $this->logging->info("add translate item locale: {$lang->code} key: {$transAttribute} value: {$newValue}", ['item' => $item]);
                }
            }
            $this->logging->info("end createTranslations", [
                'item'      => $item,
                'request'   => $request->all()
            ]);
        } catch (Exception $e) {
            $this->logging->error("error createTranslations error: " . $e->getMessage(), [
                'item'      => $item,
                'request'   => $request->all()
            ]);
        }
    }
}
