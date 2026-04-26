<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->call(SiteSettingSeeder::class);
        $this->call(DefaultCountriesAndCitiesSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(SocialsSeeder::class);
        $this->call(LlmsTxtSeeder::class);
        $this->call(SeoAnalyticsSeeder::class);
        $this->call(SeoAnalyticsSeeder::class);
    }
}
