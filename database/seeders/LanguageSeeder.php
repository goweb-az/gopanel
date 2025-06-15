<?php

namespace Database\Seeders;

use App\Models\Geography\Language;
use App\Models\Geography\Country;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = $this->list();

        foreach ($languages as $lang) {
            $code       = ucwords($lang['country_code']);
            $country    = Country::where('code', $code)->first();

            if ($country) {
                if (!Language::where('country_id', $country->id)->where('code', $lang['code'])->exists()) {
                    Language::create([
                        'country_id' => $country->id,
                        'code' => $lang['code'],
                        'name' => $lang['name'],
                        'is_active' => true,
                    ]);
                    $this->command->info("Create language {$code}");
                }
            } else {
                $this->command->error("Country not found {$code}");
            }
        }
    }



    private function list(): array
    {
        return [
            [
                'country_code' => 'AZ',
                'code' => 'az',
                'name' => 'Azərbaycan',
            ],
            [
                'country_code' => 'US',
                'code' => 'en',
                'name' => 'English',
            ],
            [
                'country_code' => 'RU',
                'code' => 'ru',
                'name' => 'Russian',
            ],
        ];
    }
}
