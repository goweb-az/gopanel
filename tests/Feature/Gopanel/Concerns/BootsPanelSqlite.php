<?php

namespace Tests\Feature\Gopanel\Concerns;

use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomPermission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Yaddaşda SQLite + panelin minimal cədvəlləri.
 *
 * Niyə: layihənin MySQL bazasına toxunulmur (test datası dev bazada qalmır),
 * bütün miqrasiyalar da SQLite-da işləmir - yalnız lazım olan cədvəllər qurulur.
 * Nümunə: tests/Feature/Gopanel/Lookups/LookupModuleTest.php.
 */
trait BootsPanelSqlite
{
    protected function bootPanelSqlite(bool $activityLog = false): void
    {
        config([
            'database.connections.panel_testing' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'    => 'panel_testing',
            'activitylog.enabled' => $activityLog,
        ]);
        DB::purge('panel_testing');

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->default(0);
            $table->string('code', 10);
            $table->string('name', 100);
            $table->boolean('default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_show')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('languages')->insert([
            ['code' => 'az', 'name' => 'Azərbaycan', 'default' => true, 'sort_order' => 1],
        ]);

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super')->default(false);
            $table->string('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gopanel_logo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('site_settings')->insert(['gopanel_logo' => null]);

        (require base_path('database/migrations/2025_03_25_180012_create_field_translations_table.php'))->up();
        (require base_path('database/migrations/2025_06_14_165226_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2025_06_25_171734_add_group_and_title_to_permissions_table.php'))->up();
        (require base_path('database/migrations/2025_04_07_114604_create_file_logs_table.php'))->up();

        require_once base_path('database/migrations/2024_09_07_125916_create_activity_log_table.php');
        (new \CreateActivityLogTable())->up();
    }

    /** @param  list<string>  $permissions */
    protected function makePanelAdmin(string $email, array $permissions = [], bool $super = false, string $name = 'Test Admin'): Admin
    {
        $admin = Admin::query()->create([
            'full_name' => $name,
            'email'     => $email,
            'password'  => bcrypt('secret'),
            'is_active' => true,
            'is_super'  => $super,
        ]);

        foreach ($permissions as $name) {
            $permission = CustomPermission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'gopanel']);
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }
}
