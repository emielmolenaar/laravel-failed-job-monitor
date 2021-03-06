<?php

namespace Spatie\FailedJobMonitor\Test;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\SyncJob;
use Spatie\FailedJobMonitor\FailedJobNotifier;

class FailedJobMonitorTest extends TestCase
{
    /** @var \Spatie\FailedJobMonitor\Test\InMemoryMailer */
    protected $mailer;

    /** @var \Spatie\FailedJobMonitor\FailedJobNotifier */
    protected $notifier;

    public function setUp()
    {
        parent::setUp();

        $this->mailer = new InMemoryMailer();
        $this->notifier = new FailedJobNotifier();

        $this->app->instance('mailer', $this->mailer);
    }

    /** @test */
    public function it_can_send_a_mail_to_the_configured_mail_address()
    {
        $this->notifier->notifyIfJobFailed('mail');

        $this->generateEvent();

        $this->assertTrue($this->mailer->hasMessageFor('your@email.com'));
    }

    /** @test */
    public function it_can_send_a_mail_with_a_subject()
    {
        $this->notifier->notifyIfJobFailed('mail');

        $this->generateEvent();

        $this->assertTrue($this->mailer->hasMessageWithSubject('A queued job has failed on http://localhost'));
    }

    /** @test */
    public function it_can_send_message_with_content()
    {
        $this->notifier->notifyIfJobFailed('mail');

        $this->generateEvent();

        $contains = false;

        foreach ($this->mailer->getMessages() as $message) {
            if ($this->mailer->hasContent('DummyEvent', $message)) {
                $contains = true;
            }
        }

        $this->assertTrue($contains);
    }

    protected function getDummyJob() : Job
    {
        return new SyncJob($this->app, '');
    }

    protected function generateEvent() : array
    {
        return event(new JobFailed('test', $this->getDummyJob(),
            [
                'data' => [
                    'command' => serialize(new DummyEvent()),
                ],
            ]

        ));
    }
}
