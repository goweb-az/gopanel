<?php

namespace Tests\Feature\Gopanel;

use App\Enums\System\LogChannelEnum;
use App\Models\Activity\FileLog;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\LogService;
use App\Support\Activity\ActivityMessage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Gopanel\Authors\Fixtures\PlainTicket;
use Tests\Feature\Gopanel\Concerns\BootsPanelSqlite;
use Tests\TestCase;

/**
 * `ActivityLogger` - bir çağırışla `activity_log` + fayl jurnalı; `LogChannelEnum`.
 * Baza: yaddaşda SQLite.
 */
class ActivityLoggerTest extends TestCase
{
    use BootsPanelSqlite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPanelSqlite(activityLog: true);

        Schema::create('test_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        config(['custom.activity_messages.PlainTicket' => [
            'title'          => 'Bilet',
            'published'      => ':causer «:title» biletini dərc etdi (:url)',
            'updated'        => ':causer bileti yenilədi - :changes',
            'password_shown' => 'Parol: :password',
        ]]);
    }

    public function test_one_call_writes_both_logs_with_same_message(): void
    {
        $admin = $this->makePanelAdmin('logger@example.com', name: 'Jurnal Admin');
        Auth::guard('gopanel')->login($admin);

        $ticket = PlainTicket::query()->create(['title' => 'Bilet 1']);

        ActivityLogger::on(LogChannelEnum::GOPANEL)
            ->subject($ticket)
            ->event('published')
            ->with(['url' => 'https://site.az/b/1'])
            ->write();

        $expected = 'Jurnal Admin «Bilet 1» biletini dərc etdi (https://site.az/b/1)';

        $activity = Activity::query()->where('event', 'published')->sole();
        $this->assertSame($expected, $activity->description);
        $this->assertSame('PlainTicket', $activity->log_name);
        $this->assertSame($admin->id, (int) $activity->causer_id);
        $this->assertSame($ticket->id, (int) $activity->subject_id);

        $file = FileLog::query()->where('channel', 'gopanel')->sole();
        $this->assertSame($expected, $file->message);
        $this->assertSame('published', $file->context['event']);
        $this->assertSame('Jurnal Admin', $file->context['causer_name']);
        $this->assertSame($admin->id, $file->context['admin_id'], 'Aktor sahələri LogService::actorContext()-dəndir.');
        $this->assertNull($file->log_details, 'info üçün backtrace yazılmır.');
    }

    public function test_string_channel_changes_and_without_activity_log(): void
    {
        $ticket = PlainTicket::query()->create(['title' => 'Köhnə']);
        $before = $ticket->getOriginal();
        $ticket->update(['title' => 'Yeni']);

        ActivityLogger::on('sms')
            ->subject($ticket)
            ->event('updated')
            ->changesFromModel($ticket, $before)
            ->withoutActivityLog()
            ->write();

        $this->assertSame(0, Activity::query()->count());

        $file = FileLog::query()->where('channel', 'sms')->sole();
        $this->assertStringContainsString('Köhnə → Yeni', $file->message);
        $this->assertStringContainsString('Sistem', $file->message, 'Konsolda causer «Sistem»-dir.');
    }

    public function test_record_shortcut_fallback_and_sensitive_placeholders(): void
    {
        ActivityLogger::record(LogChannelEnum::CRON, null, 'cleanup', ['deleted' => 5], group: 'Cleanup');

        $activity = Activity::query()->where('event', 'cleanup')->sole();
        $this->assertSame('Cleanup', $activity->log_name);
        $this->assertSame('Sistem: Cleanup - cleanup.', $activity->description);

        $ticket = new PlainTicket(['title' => 'x']);
        $this->assertSame('Parol: ***', ActivityMessage::render($ticket, 'password_shown', extra: ['password' => 'gizli']));
        $this->assertTrue(ActivityMessage::hasTemplate('PlainTicket', 'published'));
        $this->assertFalse(ActivityMessage::hasTemplate('PlainTicket', 'nope'));
    }

    public function test_logging_failure_never_breaks_caller(): void
    {
        // Yanlış səviyyə `LogService`-də istisna atır - zəncir onu udmalı, çağıranı dayandırmamalıdır
        ActivityLogger::on('gopanel')->event('x')->level('not-a-level')->write();

        $this->assertSame(0, FileLog::query()->where('channel', 'gopanel')->count());
        $this->assertSame(0, Activity::query()->count());
    }

    public function test_log_channel_enum_mirrors_logging_config_and_keeps_strings_working(): void
    {
        $channels = array_keys((array) config('logging.channels'));

        foreach (LogChannelEnum::cases() as $case) {
            $this->assertContains($case->value, $channels, "{$case->value} config/logging.php-də yoxdur.");
            $this->assertNotSame('', $case->label());
        }

        $this->assertInstanceOf(LogService::class, LogChannelEnum::SMS->log());
        $this->assertArrayHasKey('cron', LogChannelEnum::options());

        LogChannelEnum::EXPORT->log()->info('enum ilə');
        (new LogService('export'))->info('string ilə');

        $this->assertSame(2, FileLog::query()->where('channel', 'export')->count());
    }
}
