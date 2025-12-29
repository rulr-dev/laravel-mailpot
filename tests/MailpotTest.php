<?php

namespace Rulr\Mailpot\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Rulr\Mailpot\MailpotServiceProvider;
use Rulr\Mailpot\MailpotTransport;
use Rulr\Mailpot\Support\Mailpot;
use Rulr\Mailpot\Support\Statistics;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class MailpotTest extends TestCase
{
    private string $mailpotPath;

    protected function getPackageProviders($app): array
    {
        return [MailpotServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailpotPath = storage_path('framework/mailpot');
        File::deleteDirectory($this->mailpotPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->mailpotPath);
        parent::tearDown();
    }

    private function createEmail(
        string $from = 'sender@example.com',
        string $to = 'receiver@example.com',
        string $subject = 'Test Subject',
        string $html = '<p>Test</p>',
        string $text = 'Test'
    ): Email {
        return (new Email)
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($html)
            ->text($text);
    }

    private function sendEmail(Email $email): void
    {
        $transport = new MailpotTransport;
        $message = new SentMessage($email, new Envelope($email->getFrom()[0], $email->getTo()));

        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->invoke($transport, $message);
    }

    private function getStoredMessages(): \Illuminate\Support\Collection
    {
        return collect(File::files($this->mailpotPath))
            ->filter(fn ($file) => Mailpot::isMessageFile($file->getFilename()));
    }

    public function test_inbox_path_returns_correct_directory(): void
    {
        $this->assertEquals(
            storage_path('framework/mailpot'),
            Mailpot::inboxPath()
        );
    }

    public function test_ensure_inbox_directory_creates_directory(): void
    {
        $this->assertFalse(File::isDirectory($this->mailpotPath));

        Mailpot::ensureInboxDirectory();

        $this->assertTrue(File::isDirectory($this->mailpotPath));
        $this->assertTrue(File::exists($this->mailpotPath.'/.gitignore'));
    }

    public function test_ensure_inbox_directory_is_idempotent(): void
    {
        Mailpot::ensureInboxDirectory();
        Mailpot::ensureInboxDirectory();

        $this->assertTrue(File::isDirectory($this->mailpotPath));
    }

    public function test_stats_file_path_returns_correct_path(): void
    {
        $this->assertEquals(
            storage_path('framework/mailpot/stats.json'),
            Mailpot::statsFilePath()
        );
    }

    public function test_is_message_file_returns_true_for_json_files(): void
    {
        $this->assertTrue(Mailpot::isMessageFile('2024-01-01_12-00-00_abc123.json'));
        $this->assertTrue(Mailpot::isMessageFile('message.json'));
    }

    public function test_is_message_file_returns_false_for_stats_file(): void
    {
        $this->assertFalse(Mailpot::isMessageFile('stats.json'));
    }

    public function test_is_message_file_returns_false_for_non_json_files(): void
    {
        $this->assertFalse(Mailpot::isMessageFile('file.txt'));
        $this->assertFalse(Mailpot::isMessageFile('image.png'));
    }

    public function test_transport_stores_email_as_json(): void
    {
        $email = $this->createEmail(
            from: 'sender@example.com',
            to: 'receiver@example.com',
            subject: 'Test Subject',
            html: '<p>HTML content</p>',
            text: 'Plain text'
        );

        $this->sendEmail($email);

        $messages = $this->getStoredMessages();
        $this->assertCount(1, $messages);

        $data = json_decode(File::get($messages->first()->getRealPath()), true);

        $this->assertEquals('sender@example.com', $data['from']);
        $this->assertEquals(['receiver@example.com'], $data['to']);
        $this->assertEquals('Test Subject', $data['subject']);
        $this->assertEquals('<p>HTML content</p>', $data['html']);
        $this->assertEquals('Plain text', $data['text']);
        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('headers', $data);
    }

    public function test_transport_updates_stats_after_sending(): void
    {
        $email = $this->createEmail(subject: 'Stats Test');
        $this->sendEmail($email);

        $statsPath = Mailpot::statsFilePath();
        $this->assertTrue(File::exists($statsPath));

        $stats = json_decode(File::get($statsPath), true);

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals('Stats Test', $stats['latest_message']['subject']);
    }

    public function test_transport_to_string_returns_mailpot(): void
    {
        $transport = new MailpotTransport;
        $this->assertEquals('mailpot', (string) $transport);
    }

    public function test_statistics_collect_messages_returns_only_message_files(): void
    {
        Mailpot::ensureInboxDirectory();

        File::put($this->mailpotPath.'/message1.json', '{}');
        File::put($this->mailpotPath.'/message2.json', '{}');
        File::put($this->mailpotPath.'/stats.json', '{}');
        File::put($this->mailpotPath.'/other.txt', 'text');

        $messages = Statistics::collectMessages($this->mailpotPath);

        $this->assertCount(2, $messages);
    }

    public function test_statistics_generate_returns_correct_structure(): void
    {
        $email = $this->createEmail(
            from: 'test@example.com',
            subject: 'Generated Stats'
        );
        $this->sendEmail($email);

        $messages = Statistics::collectMessages($this->mailpotPath);
        $stats = Statistics::generate($messages);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('largest', $stats);
        $this->assertArrayHasKey('smallest', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
        $this->assertArrayHasKey('latest_message', $stats);

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals('Generated Stats', $stats['latest_message']['subject']);
    }

    public function test_statistics_save_and_load_stats(): void
    {
        Mailpot::ensureInboxDirectory();

        $stats = [
            'total' => 5,
            'total_size' => 1024,
            'largest' => 512,
            'smallest' => 128,
            'last_updated' => '2024-01-01 12:00:00',
            'latest_message' => [
                'date' => '2024-01-01 12:00:00',
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'subject' => 'Test',
            ],
        ];

        $statsPath = Mailpot::statsFilePath();
        Statistics::saveStats($stats, $statsPath);

        $loaded = Statistics::loadStats($statsPath);

        $this->assertEquals($stats, $loaded);
    }

    public function test_statistics_load_stats_returns_null_for_missing_file(): void
    {
        $this->assertNull(Statistics::loadStats('/nonexistent/path/stats.json'));
    }

    public function test_statistics_format_bytes_formats_correctly(): void
    {
        $this->assertEquals('0 B', Statistics::formatBytes(0));
        $this->assertEquals('500 B', Statistics::formatBytes(500));
        $this->assertEquals('1 KB', Statistics::formatBytes(1024));
        $this->assertEquals('1.5 KB', Statistics::formatBytes(1536));
        $this->assertEquals('1 MB', Statistics::formatBytes(1048576));
        $this->assertEquals('1 GB', Statistics::formatBytes(1073741824));
    }

    public function test_statistics_format_bytes_respects_precision(): void
    {
        $this->assertEquals('1.5 KB', Statistics::formatBytes(1536, 1));
        $this->assertEquals('1.5 KB', Statistics::formatBytes(1536, 2));
        $this->assertEquals('1.56 KB', Statistics::formatBytes(1600, 2));
    }

    public function test_clean_command_deletes_messages(): void
    {
        $this->sendEmail($this->createEmail());
        $this->sendEmail($this->createEmail());

        $this->assertCount(2, $this->getStoredMessages());

        $this->artisan('mailpot:clean')
            ->expectsOutputToContain('Cleaned 2 message(s)')
            ->expectsConfirmation('Do you also want to delete the stats file?', 'no')
            ->assertExitCode(0);

        $this->assertCount(0, $this->getStoredMessages());
        $this->assertTrue(File::exists(Mailpot::statsFilePath()));
    }

    public function test_clean_command_deletes_stats_when_confirmed(): void
    {
        $this->sendEmail($this->createEmail());

        $this->artisan('mailpot:clean')
            ->expectsConfirmation('Do you also want to delete the stats file?', 'yes')
            ->assertExitCode(0);

        $this->assertFalse(File::exists(Mailpot::statsFilePath()));
    }

    public function test_stats_command_displays_statistics(): void
    {
        $this->sendEmail($this->createEmail(subject: 'Stats Display Test'));

        $this->artisan('mailpot:stats')
            ->expectsOutputToContain('Mailpot Inbox Stats')
            ->expectsOutputToContain('Total messages:')
            ->assertExitCode(0);
    }

    public function test_stats_command_shows_warning_when_no_messages(): void
    {
        Mailpot::ensureInboxDirectory();

        $this->artisan('mailpot:stats')
            ->expectsOutputToContain('No messages and no stats file found')
            ->assertExitCode(0);
    }

    public function test_stats_command_loads_from_saved_stats_when_no_messages(): void
    {
        $this->sendEmail($this->createEmail());

        foreach ($this->getStoredMessages() as $file) {
            File::delete($file->getRealPath());
        }

        $this->artisan('mailpot:stats')
            ->expectsOutputToContain('From Previously Saved Data')
            ->assertExitCode(0);
    }

    public function test_multiple_emails_updates_stats_correctly(): void
    {
        $this->sendEmail($this->createEmail(subject: 'First'));
        $this->sendEmail($this->createEmail(subject: 'Second'));
        $this->sendEmail($this->createEmail(subject: 'Third'));

        $stats = Statistics::loadStats(Mailpot::statsFilePath());

        $this->assertNotNull($stats);
        $this->assertEquals(3, $stats['total']);
        $this->assertContains($stats['latest_message']['subject'], ['First', 'Second', 'Third']);
    }
}
