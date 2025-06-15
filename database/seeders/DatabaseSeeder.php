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
        $this->call(DefaultCountriesAndCitiesSeeder::class);
        $this->call(LanguageSeeder::class);
        // $this->call(RolesTableSeeder::class);
        // $this->call(AzCitiesSeeder::class);
        // $this->call(TranslationSeeder::class);
    }
}
