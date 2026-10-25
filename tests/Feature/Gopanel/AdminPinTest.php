<?php

namespace Tests\Feature\Gopanel;

use App\Http\Middleware\Gopanel\RequireAdminPin;
use App\Models\Gopanel\Admin;
use App\Services\Gopanel\Admins\AdminPinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Admin PIN-i: middleware (gopanel.pin), təsdiq axını, profil PIN-i.
 *
 * Baza: yaddaşda SQLite - layihənin MySQL bazasına toxunulmur.
 */
class AdminPinTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.pin_testing' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
            'database.default'    => 'pin_testing',
            'activitylog.enabled' => false,
            'custom.security.admin_pin.enabled' => true,
        ]);
        DB::purge('pin_testing');

        // `web` qrupundakı HumanGate tənzimləmə sətrini oxuyur
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
            $table->string('pin')->nullable();
            $table->timestamp('pin_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super')->default(false);
            $table->string('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->admin = Admin::query()->create([
            'full_name' => 'PIN Admin',
            'email'     => 'pin@example.com',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);

        // Real gopanel zənciri + qorunan test route-ları
        Route::middleware(['web', 'gopanel', 'gopanel.pin'])->group(function () {
            Route::get('/__pin-test/download', fn () => 'downloaded')->name('pin-test.download');
            Route::post('/__pin-test/delete', fn () => response()->json(['status' => 'success']))->name('pin-test.delete');
        });
        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_pin_column_is_hidden_and_not_mass_assignable(): void
    {
        $this->assertContains('pin', $this->admin->getHidden());
        $this->assertNotContains('pin', $this->admin->getFillable());
        $this->assertFalse($this->admin->hasPin());
    }

    public function test_backup_download_route_is_guarded_by_pin_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('gopanel.backup.download');

        $this->assertNotNull($route);
        $this->assertContains('gopanel.pin', $route->gatherMiddleware());
    }

    public function test_nothing_is_asked_when_feature_is_disabled_or_admin_has_no_pin(): void
    {
        // PIN yoxdur → keçir
        $this->actingAs($this->admin, 'gopanel')->get('/__pin-test/download')->assertOk()->assertSee('downloaded');

        // PIN var, amma mexanizm sönük (default) → keçir
        app(AdminPinService::class)->set($this->admin, '1234');
        session()->flush();
        config(['custom.security.admin_pin.enabled' => false]);

        $this->actingAs($this->admin, 'gopanel')->get('/__pin-test/download')->assertOk();
        $this->actingAs($this->admin, 'gopanel')->postJson('/__pin-test/delete')->assertOk();
    }

    public function test_ajax_request_gets_423_and_get_link_is_redirected_to_prompt(): void
    {
        $this->givePin('1234');

        $this->actingAs($this->admin, 'gopanel')
            ->postJson('/__pin-test/delete')
            ->assertStatus(423)
            ->assertJson(['status' => 'error', 'pin_required' => true]);

        $this->actingAs($this->admin, 'gopanel')
            ->get('/__pin-test/download')
            ->assertRedirect(route('gopanel.pin.prompt'))
            ->assertSessionHas(RequireAdminPin::INTENDED_KEY, url('/__pin-test/download'));
    }

    public function test_wrong_pin_is_rejected_and_throttled(): void
    {
        config(['custom.security.admin_pin.throttle.attempts' => 2]);
        $this->givePin('1234');

        $this->actingAs($this->admin, 'gopanel')->postJson(route('gopanel.pin.confirm'), ['pin' => '0000'])->assertStatus(422);
        $this->actingAs($this->admin, 'gopanel')->postJson(route('gopanel.pin.confirm'), ['pin' => '1111'])->assertStatus(422);

        // Limit doldu - düzgün PIN də qəbul edilmir
        $this->actingAs($this->admin, 'gopanel')
            ->postJson(route('gopanel.pin.confirm'), ['pin' => '1234'])
            ->assertStatus(429);

        $this->actingAs($this->admin, 'gopanel')->postJson('/__pin-test/delete')->assertStatus(423);
    }

    public function test_correct_pin_confirms_session_and_returns_to_intended_url(): void
    {
        $this->givePin('1234');

        $this->actingAs($this->admin, 'gopanel')->get('/__pin-test/download')->assertRedirect(route('gopanel.pin.prompt'));

        // Səhifədən gələn adi POST → hədəfə yönləndirmə
        $this->actingAs($this->admin, 'gopanel')
            ->post(route('gopanel.pin.confirm'), ['pin' => '1234'])
            ->assertRedirect(url('/__pin-test/download'));

        // TTL ərzində yenidən soruşulmur
        $this->actingAs($this->admin, 'gopanel')->get('/__pin-test/download')->assertOk();
        $this->actingAs($this->admin, 'gopanel')->postJson('/__pin-test/delete')->assertOk();

        // TTL bitəndə yenə soruşulur
        $this->travel(16)->minutes();
        $this->actingAs($this->admin, 'gopanel')->postJson('/__pin-test/delete')->assertStatus(423);
    }

    public function test_ajax_confirm_returns_success_json(): void
    {
        $this->givePin('1234');

        $this->actingAs($this->admin, 'gopanel')
            ->postJson(route('gopanel.pin.confirm'), ['pin' => '1234'])
            ->assertOk()
            ->assertJson(['status' => 'success', 'redirect' => false]);
    }

    public function test_admin_sets_and_clears_own_pin_with_current_password(): void
    {
        $url = route('gopanel.profile.pin.update');

        // Şifrəsiz / səhv şifrə ilə olmaz
        $this->actingAs($this->admin, 'gopanel')
            ->postJson($url, ['pin' => '4321', 'pin_confirmation' => '4321', 'current_password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        // Hərf və qısa PIN qəbul edilmir
        $this->actingAs($this->admin, 'gopanel')
            ->postJson($url, ['pin' => '12a', 'pin_confirmation' => '12a', 'current_password' => 'secret123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pin');

        $this->actingAs($this->admin, 'gopanel')
            ->postJson($url, ['pin' => '4321', 'pin_confirmation' => '4321', 'current_password' => 'secret123'])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $fresh = $this->admin->fresh();
        $this->assertTrue($fresh->hasPin());
        $this->assertNotSame('4321', $fresh->pin);
        $this->assertTrue(Hash::check('4321', $fresh->pin));
        $this->assertNotNull($fresh->pin_updated_at);

        $this->actingAs($this->admin, 'gopanel')
            ->postJson($url, ['clear' => 1, 'current_password' => 'secret123'])
            ->assertOk();

        $this->assertFalse($this->admin->fresh()->hasPin());
    }

    public function test_prompt_skips_to_intended_when_confirmation_is_not_needed(): void
    {
        session([RequireAdminPin::INTENDED_KEY => url('/__pin-test/download')]);

        $this->actingAs($this->admin, 'gopanel')
            ->get(route('gopanel.pin.prompt'))
            ->assertRedirect(url('/__pin-test/download'));
    }

    private function givePin(string $pin): void
    {
        app(AdminPinService::class)->set($this->admin, $pin);
        // set() sessiyanı təsdiqli edir - testdə «təsdiqlənməmiş» vəziyyətdən başlanır
        app(AdminPinService::class)->revoke();
        $this->admin->refresh();
    }
}
