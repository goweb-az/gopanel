<?php

namespace Database\Seeders;

use App\Models\Geography\City;
use App\Models\Geography\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            $country = Country::where("code", $data['country_code'])->first();
            if (isset($country->id)) {
                if (!City::where("country_id", $country->id)->where("name", $data['name'])->exists()) {
                    $city               = new City();
                    $city->country_id   = $country->id;
                    $city->name         = $data['name'];
                    $city->district     = $data['district'] ?? NULL;
                    $city->postal_code  = $data['postal_code'] ?? NULL;
                    $city->latitude     = $data['latitude'] ?? NULL;
                    $city->longitude    = $data['longitude'] ?? NULL;
                    $city->population   = $data['population'] ?? NULL;
                    $city->area         = $data['area'] ?? NULL;
                    $city->is_active    = true;
                    $city->order        = $order;
                    if ($city->save())
                        $order++;
                }
            }
        }
    }
}
