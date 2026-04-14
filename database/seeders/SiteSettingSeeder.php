<?php

namespace Database\Seeders;

use App\Models\Gopanel\Admin;
use App\Models\Settings\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exists = SiteSetting::first();
        if (is_null($exists)) {
            $setting                    = new SiteSetting();
            $setting->site_status       = 1;
            $setting->login_status      = 1;
            $setting->register_status   = 1;
            $setting->payment_status    = 1;
            $setting->logo_light        = '/assets/gopanel/images/gopanel-logo.png';
            $setting->logo_dark         = '/assets/gopanel/images/gopanel-logo.png';
            $setting->mail_logo         = '/assets/gopanel/images/gopanel-logo.png';
            $setting->gopanel_logo      = '/assets/gopanel/images/gopanel-logo.png';
            $setting->save();
        }
    }
}
