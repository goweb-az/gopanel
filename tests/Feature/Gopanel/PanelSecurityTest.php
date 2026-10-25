<?php

namespace Tests\Feature\Gopanel;

use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomPermission;
use App\Traits\System\LeanPermissionChecks;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Panel təhlükəsizliyi: IP allowlist (ip.restrict), giriş limiti, LeanPermissionChecks.
 *
 * Baza: yaddaşda SQLite - layihənin MySQL bazasına toxunulmur.
 */
class PanelSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.security_testing' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'    => 'security_testing',
            'activitylog.enabled' => false,
        ]);
        DB::purge('security_testing');

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('site_status')->default(true);
            $table->boolean('block_bad_bots')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('site_settings')->insert(['site_status' => true]);

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->nullable();
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

        Route::middleware(['web', 'ip.restrict'])->get('/__ip-test', fn () => 'ok');
    }

    // ── IP allowlist ─────────────────────────────────────────────────────

    public function test_ip_restriction_is_off_by_default(): void
    {
        $this->assertFalse(config('custom.security.ip_restriction_enabled'));

        $this->get('/__ip-test')->assertOk();
    }

    public function test_panel_route_group_carries_ip_restriction(): void
    {
        $route = app('router')->getRoutes()->getByName('gopanel.auth.login');

        $this->assertContains('ip.restrict', $route->gatherMiddleware());
    }

    public function test_allowlist_accepts_exact_ip_and_cidr_and_blocks_others(): void
    {
        config([
            'custom.security.ip_restriction_enabled' => true,
            'custom.security.allowed_ips'            => "10.0.0.0/8,\r\n 192.168.1.5",
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.5'])->get('/__ip-test')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->get('/__ip-test')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/__ip-test')->assertForbidden();
    }

    public function test_enabled_with_empty_allowlist_fails_closed(): void
    {
        config(['custom.security.ip_restriction_enabled' => true, 'custom.security.allowed_ips' => '']);

        $this->get('/__ip-test')->assertForbidden();
    }

    public function test_restricted_domains_limit_the_check_to_listed_hosts(): void
    {
        config([
            'custom.security.ip_restriction_enabled' => true,
            'custom.security.allowed_ips'            => '1.1.1.1',
            'custom.security.restricted_domains'     => 'panel.example.az',
        ]);

        $this->get('http://localhost/__ip-test')->assertOk();
        $this->get('http://panel.example.az/__ip-test')->assertForbidden();
    }

    public function test_cloudflare_header_is_ignored_unless_trusted(): void
    {
        config([
            'custom.security.ip_restriction_enabled' => true,
            'custom.security.allowed_ips'            => '1.1.1.1',
        ]);

        // Saxta başlıq allowlist-i keçə bilməməlidir
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->get('/__ip-test', ['CF-Connecting-IP' => '1.1.1.1'])
            ->assertForbidden();

        config(['custom.security.trust_cf_header' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->get('/__ip-test', ['CF-Connecting-IP' => '1.1.1.1'])
            ->assertOk();
    }

    // ── Giriş limiti ─────────────────────────────────────────────────────

    public function test_login_is_locked_after_max_failed_attempts_and_success_clears_counter(): void
    {
        config(['custom.security.login_throttle.max_attempts' => 2]);
        $this->makeAdmin('login@example.com');
        $url = route('gopanel.auth.login.proccess');

        $this->postJson($url, ['email' => 'login@example.com', 'password' => 'wrong'])->assertStatus(403);
        $this->postJson($url, ['email' => 'login@example.com', 'password' => 'wrong'])->assertStatus(403);

        // Limit doldu - düzgün şifrə də qəbul edilmir
        $this->postJson($url, ['email' => 'login@example.com', 'password' => 'secret123'])
            ->assertStatus(429)
            ->assertJson(['status' => 'error']);

        // Başqa ünvan eyni IP-dən ayrıca sayılır (qəsdən kilidləmə yoxdur)
        $this->makeAdmin('other@example.com');
        $this->postJson($url, ['email' => 'other@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function test_login_throttle_can_be_disabled(): void
    {
        config(['custom.security.login_throttle.max_attempts' => 0]);
        $this->makeAdmin('free@example.com');
        $url = route('gopanel.auth.login.proccess');

        foreach (range(1, 4) as $i) {
            $this->postJson($url, ['email' => 'free@example.com', 'password' => 'wrong'])->assertStatus(403);
        }

        $this->postJson($url, ['email' => 'free@example.com', 'password' => 'secret123'])->assertOk();
    }

    // ── LeanPermissionChecks ─────────────────────────────────────────────

    public function test_lean_permission_checks_read_role_and_direct_permissions(): void
    {
        (require base_path('database/migrations/2025_06_14_165226_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2025_06_25_171734_add_group_and_title_to_permissions_table.php'))->up();

        $admin = LeanAdmin::query()->create([
            'full_name' => 'Lean',
            'email'     => 'lean@example.com',
            'password'  => Hash::make('secret123'),
        ]);

        $direct = CustomPermission::query()->create(['name' => 'gopanel.blog.index', 'guard_name' => 'gopanel']);
        $viaRole = CustomPermission::query()->create(['name' => 'gopanel.blog.edit', 'guard_name' => 'gopanel']);
        CustomPermission::query()->create(['name' => 'gopanel.blog.delete', 'guard_name' => 'gopanel']);

        $role = Role::query()->create(['name' => 'Redaktor', 'guard_name' => 'gopanel']);
        $role->givePermissionTo($viaRole);
        $admin->givePermissionTo($direct);
        $admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($admin->hasLeanPermission('gopanel.blog.index'));
        $this->assertTrue($admin->hasLeanPermission('gopanel.blog.edit'));
        $this->assertFalse($admin->hasLeanPermission('gopanel.blog.delete'));
        $this->assertFalse($admin->hasLeanPermission('gopanel.blog.index', 'web'));

        // Sorğu daxilində memoize - yeni icazə forget-dən sonra görünür
        $admin->givePermissionTo('gopanel.blog.delete');
        $this->assertFalse($admin->hasLeanPermission('gopanel.blog.delete'));
        $admin->forgetLeanPermissionCache();
        $this->assertTrue($admin->hasLeanPermission('gopanel.blog.delete'));
    }

    private function makeAdmin(string $email): Admin
    {
        return Admin::query()->create([
            'full_name' => 'Test',
            'email'     => $email,
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);
    }
}

/** Trait-i Admin-ə qoşmadan yoxlamaq üçün (starter-də opt-in). */
class LeanAdmin extends Admin
{
    use LeanPermissionChecks;

    protected $table = 'admins';

    /** Alt sinif auth provider-də yoxdur - Spatie guard-ı özü tapa bilmir. */
    protected $guard_name = 'gopanel';

    public function getMorphClass()
    {
        return Admin::class;
    }
}
