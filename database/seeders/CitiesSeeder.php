<?php


namespace Database\Seeders;

use App\Models\Geography\City;
use App\Models\Geography\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CitiesSeeder extends Seeder
{
    public function run()
    {
        // Önce ülkeleri al
        $countries = Country::all();

        foreach ($countries as $country) {
            // $citiesResponse = Http::get('http://api.geonames.org/postalCodeLookupJSON', [
            //     'postalcode'    => $country->postal_code,
            //     'country'       => $country->code,
            //     'maxRows'       => 1000,
            //     'username'      => 'orucseyidov',
            // ]);
            // if ($citiesResponse->successful()) {
            //     // $cities = $citiesResponse->json('geonames');
            //     $cities = $citiesResponse->json('geonames', []);
            //     foreach ($cities as $city) {
            //         if (!is_array($city)) {
            //             continue;
            //         }
            //         City::insert([
            //             'name' => $city['name'] ?? null,
            //             'country_id' => $country->id,
            //             'state' => $city['adminName1'] ?? null,
            //             'postal_code' => $city['postalCode'] ?? null,
            //             'latitude' => $city['lat'] ?? null,
            //             'longitude' => $city['lng'] ?? null,
            //             'population' => $city['population'] ?? null,
            //             'area' => $city['area'] ?? null,
            //         ]);
            //     }
            // } else {
            //     Log::error('API error: ' . $citiesResponse->status());
            //     $cities = [];
            // }
        }
    }
}
