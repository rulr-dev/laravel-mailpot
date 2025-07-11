<?php

namespace Rulr\Mailpot\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Rulr\Mailpot\MailpotServiceProvider;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Envelope;
use Rulr\Mailpot\MailpotTransport;

class MailpotTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MailpotServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailpotPath = storage_path('framework/mailpot');
        File::deleteDirectory($this->mailpotPath);
    }

    /** @test */
    public function test_it_stores_sent_mail_as_json()
    {
        $transport = new MailpotTransport();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('receiver@example.com')
            ->subject('Test Subject')
            ->text('This is the plain text')
            ->html('<p>This is the <strong>HTML</strong> version</p>');

        $message = new SentMessage($email, new Envelope($email->getFrom()[0], $email->getTo()));

        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $message);

        $files = collect(File::files($this->mailpotPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.json') && $file->getFilename() !== 'stats.json');

        $this->assertCount(1, $files);

        $data = json_decode(File::get($files->first()->getRealPath()), true);

        $this->assertEquals('sender@example.com', $data['from']);
        $this->assertEquals(['receiver@example.com'], $data['to']);
        $this->assertEquals('Test Subject', $data['subject']);
        $this->assertEquals('<p>This is the <strong>HTML</strong> version</p>', $data['html']);
    }

    /** @test */
    public function test_it_updates_stats_file()
    {
        $transport = new MailpotTransport();

        $email = (new Email())
            ->from('admin@rulr.dev')
            ->to('user@local.test')
            ->subject('Stats Update')
            ->html('<p>Testing</p>');

        $message = new SentMessage($email, new Envelope($email->getFrom()[0], $email->getTo()));

        $reflection = new \ReflectionClass($transport);
        $method = $reflection->getMethod('doSend');
        $method->setAccessible(true);
        $method->invoke($transport, $message);

        $statsPath = $this->mailpotPath . '/stats.json';

        $this->assertTrue(File::exists($statsPath));

        $data = json_decode(File::get($statsPath), true);

        $this->assertEquals(1, $data['total']);
        $this->assertEquals('Stats Update', $data['latest_message']['subject']);
    }
}
