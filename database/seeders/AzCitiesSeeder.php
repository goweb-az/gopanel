<?php

namespace Database\Seeders;

use App\Models\Geography\City;
use App\Models\Geography\Country;
use Illuminate\Database\Seeder;

class AzCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = require base_path('mockdata/geography/cities/az_cities.php');

        // Şehir verilerini cities tablosuna ekliyoruz
        $order = 0;
        foreach ($cities as $data) {
            $country = Country::where('code', $data['country_code'])->first();
            if (isset($country->id)) {
                if (! City::where('country_id', $country->id)->where('name', $data['name'])->exists()) {
                    $city = new City;
                    $city->country_id = $country->id;
                    $city->name = $data['name'];
                    $city->district = $data['district'] ?? null;
                    $city->postal_code = $data['postal_code'] ?? null;
                    $city->latitude = $data['latitude'] ?? null;
                    $city->longitude = $data['longitude'] ?? null;
                    $city->population = $data['population'] ?? null;
                    $city->area = $data['area'] ?? null;
                    $city->is_active = true;
                    $city->order = $order;
                    if ($city->save()) {
                        $order++;
                    }
                }
            }
        }
    }
}
