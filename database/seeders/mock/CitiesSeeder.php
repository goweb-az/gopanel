<?php

namespace Database\Seeders\mock;

use App\Models\Geography\City;
use App\Models\Geography\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CitiesSeeder extends Seeder
{
    public string $mockName = 'Country and city';

    public function run(): void
    {
        $hasCitiesTable = Schema::hasTable('cities');

        foreach ($this->countries() as $countryData) {
            $country = Country::updateOrCreate(
                ['code' => $countryData['code']],
                $countryData
            );

            if (!$hasCitiesTable) {
                $this->command?->warn('  - cities cedveli yoxdur, yalniz olke yenilendi: ' . $country->name);
                continue;
            }

            foreach ($this->cities()[$countryData['code']] ?? [] as $cityData) {
                City::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name'       => $cityData['name'],
                    ],
                    array_merge($cityData, [
                        'country_id' => $country->id,
                        'is_active'  => $cityData['is_active'] ?? true,
                    ])
                );
            }

            $this->command?->line('  - ' . $country->name . ': ' . count($this->cities()[$countryData['code']] ?? []) . ' seher');
        }
    }

    private function countries(): array
    {
        return [
            [
                'code' => 'AZ',
                'name' => 'Azerbaijan',
                'phone' => '994',
                'symbol' => 'AZN',
                'capital' => 'Baku',
                'currency' => 'AZN',
                'continent' => 'Asia',
                'continent_code' => 'AS',
                'alpha_3' => 'AZE',
            ],
            [
                'code' => 'TR',
                'name' => 'Turkey',
                'phone' => '90',
                'symbol' => 'TRY',
                'capital' => 'Ankara',
                'currency' => 'TRY',
                'continent' => 'Asia',
                'continent_code' => 'AS',
                'alpha_3' => 'TUR',
            ],
            [
                'code' => 'RU',
                'name' => 'Russia',
                'phone' => '7',
                'symbol' => 'RUB',
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
                'symbol' => 'USD',
                'capital' => 'Washington, D.C.',
                'currency' => 'USD',
                'continent' => 'North America',
                'continent_code' => 'NA',
                'alpha_3' => 'USA',
            ],
            [
                'code' => 'GB',
                'name' => 'United Kingdom',
                'phone' => '44',
                'symbol' => 'GBP',
                'capital' => 'London',
                'currency' => 'GBP',
                'continent' => 'Europe',
                'continent_code' => 'EU',
                'alpha_3' => 'GBR',
            ],
            [
                'code' => 'DE',
                'name' => 'Germany',
                'phone' => '49',
                'symbol' => 'EUR',
                'capital' => 'Berlin',
                'currency' => 'EUR',
                'continent' => 'Europe',
                'continent_code' => 'EU',
                'alpha_3' => 'DEU',
            ],
            [
                'code' => 'AE',
                'name' => 'United Arab Emirates',
                'phone' => '971',
                'symbol' => 'AED',
                'capital' => 'Abu Dhabi',
                'currency' => 'AED',
                'continent' => 'Asia',
                'continent_code' => 'AS',
                'alpha_3' => 'ARE',
            ],
            [
                'code' => 'GE',
                'name' => 'Georgia',
                'phone' => '995',
                'symbol' => 'GEL',
                'capital' => 'Tbilisi',
                'currency' => 'GEL',
                'continent' => 'Asia',
                'continent_code' => 'AS',
                'alpha_3' => 'GEO',
            ],
        ];
    }

    private function cities(): array
    {
        return [
            'AZ' => [
                ['name' => 'Baku', 'state' => 'Absheron', 'postal_code' => 'AZ1000', 'latitude' => 40.4093, 'longitude' => 49.8671, 'population' => 2300000],
                ['name' => 'Ganja', 'state' => 'Ganja-Dashkasan', 'postal_code' => 'AZ2000', 'latitude' => 40.6828, 'longitude' => 46.3606, 'population' => 335000],
                ['name' => 'Sumqayit', 'state' => 'Absheron', 'postal_code' => 'AZ5000', 'latitude' => 40.5855, 'longitude' => 49.6317, 'population' => 345000],
                ['name' => 'Shaki', 'state' => 'Shaki-Zagatala', 'postal_code' => 'AZ5500', 'latitude' => 41.1919, 'longitude' => 47.1706, 'population' => 68000],
            ],
            'TR' => [
                ['name' => 'Istanbul', 'state' => 'Marmara', 'postal_code' => '34000', 'latitude' => 41.0082, 'longitude' => 28.9784, 'population' => 15600000],
                ['name' => 'Ankara', 'state' => 'Central Anatolia', 'postal_code' => '06000', 'latitude' => 39.9334, 'longitude' => 32.8597, 'population' => 5700000],
                ['name' => 'Izmir', 'state' => 'Aegean', 'postal_code' => '35000', 'latitude' => 38.4237, 'longitude' => 27.1428, 'population' => 4400000],
            ],
            'RU' => [
                ['name' => 'Moscow', 'state' => 'Moscow', 'postal_code' => '101000', 'latitude' => 55.7558, 'longitude' => 37.6173, 'population' => 13000000],
                ['name' => 'Saint Petersburg', 'state' => 'Northwestern', 'postal_code' => '190000', 'latitude' => 59.9311, 'longitude' => 30.3609, 'population' => 5600000],
            ],
            'US' => [
                ['name' => 'New York', 'state' => 'New York', 'postal_code' => '10001', 'latitude' => 40.7128, 'longitude' => -74.0060, 'population' => 8400000],
                ['name' => 'San Francisco', 'state' => 'California', 'postal_code' => '94102', 'latitude' => 37.7749, 'longitude' => -122.4194, 'population' => 815000],
                ['name' => 'Austin', 'state' => 'Texas', 'postal_code' => '73301', 'latitude' => 30.2672, 'longitude' => -97.7431, 'population' => 980000],
            ],
            'GB' => [
                ['name' => 'London', 'state' => 'England', 'postal_code' => 'SW1A', 'latitude' => 51.5074, 'longitude' => -0.1278, 'population' => 9000000],
                ['name' => 'Manchester', 'state' => 'England', 'postal_code' => 'M1', 'latitude' => 53.4808, 'longitude' => -2.2426, 'population' => 550000],
            ],
            'DE' => [
                ['name' => 'Berlin', 'state' => 'Berlin', 'postal_code' => '10115', 'latitude' => 52.5200, 'longitude' => 13.4050, 'population' => 3700000],
                ['name' => 'Munich', 'state' => 'Bavaria', 'postal_code' => '80331', 'latitude' => 48.1351, 'longitude' => 11.5820, 'population' => 1500000],
            ],
            'AE' => [
                ['name' => 'Dubai', 'state' => 'Dubai', 'postal_code' => null, 'latitude' => 25.2048, 'longitude' => 55.2708, 'population' => 3600000],
                ['name' => 'Abu Dhabi', 'state' => 'Abu Dhabi', 'postal_code' => null, 'latitude' => 24.4539, 'longitude' => 54.3773, 'population' => 1500000],
            ],
            'GE' => [
                ['name' => 'Tbilisi', 'state' => 'Tbilisi', 'postal_code' => '0100', 'latitude' => 41.7151, 'longitude' => 44.8271, 'population' => 1200000],
                ['name' => 'Batumi', 'state' => 'Adjara', 'postal_code' => '6000', 'latitude' => 41.6168, 'longitude' => 41.6367, 'population' => 170000],
            ],
        ];
    }
}
