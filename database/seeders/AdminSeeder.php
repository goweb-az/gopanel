<?php

namespace Database\Seeders;

use App\Models\Gopanel\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exists = Admin::where('email', 'admin@gmail.com')->exists();
        if (! $exists) {
            $admin = new Admin();
            $admin->full_name = "Super Admin";
            $admin->email     = "admin@gmail.com";
            $admin->password  = Hash::make('123456');
            $admin->is_active = true;
            $admin->is_super  = true;
            $admin->save();
        }
    }
}
