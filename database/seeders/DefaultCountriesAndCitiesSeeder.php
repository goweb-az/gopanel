<?php

namespace Database\Seeders;

use App\Models\Geography\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DefaultCountriesAndCitiesSeeder extends Seeder
{
    public function run()
    {
        $countries = $this->list();

        foreach ($countries as $country) {
            if (!Country::where("code", $country['code'])->where("alpha_3", $country['alpha_3'])->exists()) {
                Country::create([
                    'code' => $country['code'],
                    'name' => $country['name'],
                    'phone' => $country['phone'],
                    'symbol' => $country['symbol'],
                    'capital' => $country['capital'],
                    'currency' => $country['currency'],
                    'continent' => $country['continent'],
                    'continent_code' => $country['continent_code'],
                    'alpha_3' => $country['alpha_3'],
                    'is_active' => true,
                ]);
            }
        }
    }


    private function list(): array
    {
        return [
            [
                'code' => 'AZ',
                'name' => 'Azerbaijan',
                'phone' => '994',
                'symbol' => '₼',
                'capital' => 'Baku',
                'currency' => 'AZN',
                'continent' => 'Asia',
                'continent_code' => 'AS',
                'alpha_3' => 'AZE',
            ],
            [
                'code' => 'RU',
                'name' => 'Russia',
                'phone' => '7',
                'symbol' => '₽',
                'capital' => 'Moscow',
                'currency' => 'RUB',
                'continent' => 'Europe',
                'continent_code' => 'EU',
                'alpha_3' => 'RUS',
            ],
            [
                'code' => 'US',
                'name' => 'United States',
                'phone' => '1',
                'symbol' => '$',
                'capital' => 'Washington, D.C.',
                'currency' => 'USD',
                'continent' => 'North America',
                'continent_code' => 'NA',
                'alpha_3' => 'USA',
            ],
        ];
    }
}
