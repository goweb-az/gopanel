<?php

namespace Tests\Feature\Gopanel;

use App\Helpers\Common\ActivityLogHelper;
use App\Helpers\Common\Timer;
use App\Models\Activity\FileLog;
use App\Services\Activity\LogService;
use App\Support\Activity\ActivityChangeFormatter;
use App\Support\Activity\ActivitySubject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Fayl jurnalı (LogService) və fəaliyyət jurnalının ortaq qatı.
 */
class ActivityLoggingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_info_log_is_written_to_database_without_details(): void
    {
        $message = 'Test info ' . uniqid();

        (new LogService('testing'))->info($message, ['key' => 'value']);

        $log = FileLog::where('message', $message)->first();

        $this->assertNotNull($log);
        $this->assertSame('info', $log->level);
        $this->assertNull($log->log_details, 'Adi info üçün backtrace yazılmamalıdır');
    }

    public function test_error_level_always_saves_details(): void
    {
        $message = 'Test error ' . uniqid();

        (new LogService('testing'))->error($message);

        $this->assertNotNull(FileLog::where('message', $message)->value('log_details'));
    }

    public function test_detail_flag_saves_details_for_info(): void
    {
        $message = 'Test detail ' . uniqid();

        (new LogService('testing', true, true))->info($message);

        $this->assertNotNull(FileLog::where('message', $message)->value('log_details'));
    }

    public function test_odd_context_types_do_not_crash(): void
    {
        $service = new LogService('testing', false);

        $service->info('collection', collect([1, 2]));
        $service->info('string', 'xam mətn');
        $service->info('null', null);

        $this->assertTrue(true);
    }

    public function test_unknown_level_is_a_programmer_error(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new LogService('testing'))->inof('yazı səhvi');
    }

    public function test_actor_context_is_safe_without_session(): void
    {
        $ctx = LogService::actorContext();

        $this->assertSame(['user_uid', 'user', 'admin_id', 'admin'], array_keys($ctx));
        $this->assertSame('', LogService::actorLabel($ctx));
    }

    public function test_change_formatter_builds_readable_sentence(): void
    {
        $sentence = ActivityChangeFormatter::sentence(
            'Blog',
            ['name' => 'Yeni', 'is_active' => true, 'password' => 'secret', 'start_time' => '10:00:00'],
            ['name' => 'Köhnə', 'is_active' => false, 'start_time' => '09:00:00'],
        );

        $this->assertStringContainsString('ad: Köhnə → Yeni', $sentence);
        $this->assertStringContainsString('aktivlik: xeyr → bəli', $sentence);
        $this->assertStringContainsString('10:00', $sentence);
        $this->assertStringNotContainsString('secret', $sentence, 'Həssas sahə mətnə düşməməlidir');
    }

    public function test_change_rows_respect_hidden_attributes(): void
    {
        $rows = ActivityChangeFormatter::rows('Blog', ['id' => 5, 'name' => 'A'], [], [], ['id']);

        $this->assertCount(1, $rows);
        $this->assertSame('name', $rows[0]['key']);
        $this->assertFalse($rows[0]['has_old']);
    }

    public function test_shared_config_keys_are_not_listed_as_models(): void
    {
        // attribute_labels-in içində 'title' => 'başlıq' var - yalnız title-a baxmaq kifayət etmir
        $models = collect(ActivityLogHelper::modelBlocks())->keys();

        $this->assertNotContains('attribute_labels', $models);
        $this->assertNotContains('technical_attributes', $models);
        $this->assertContains('User', $models);
    }

    public function test_causer_resolution_is_system_outside_requests(): void
    {
        $this->assertNull(ActivityLogHelper::resolveCauser());
        $this->assertSame('Sistem', ActivityLogHelper::causerName());
    }

    public function test_activity_subject_resolves_class_names(): void
    {
        $this->assertSame([FileLog::class], ActivitySubject::types(FileLog::class));
        $this->assertSame(FileLog::class, ActivitySubject::modelClass(FileLog::class));
        $this->assertNull(ActivitySubject::modelClass('Movcud\\Olmayan'));
    }

    public function test_timer_and_cron_channel_exist(): void
    {
        $this->assertArrayHasKey('cron', config('logging.channels'));
        $this->assertGreaterThanOrEqual(0, Timer::start()->elapsed());
    }

    public function test_cleanup_commands_are_registered(): void
    {
        $commands = array_keys(\Artisan::all());

        $this->assertContains('logs:cleanup', $commands);
        $this->assertContains('activity-logs:cleanup', $commands);
    }
}
