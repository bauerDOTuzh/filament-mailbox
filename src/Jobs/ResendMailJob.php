<?php

namespace Bauerdot\FilamentMailBox\Jobs;

use Bauerdot\FilamentMailBox\Models\MailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ResendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mailLog;

    public $to;

    public $cc;

    public $bcc;

    public $includeAttachments = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MailLog $mailLog, array $to = [], array $cc = [], array $bcc = [], bool $includeAttachments = false)
    {
        $this->mailLog = $mailLog;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->includeAttachments = $includeAttachments;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailer = $this->mailLog->mailer ?? config('mail.default');

        Mail::mailer($mailer)->send('filament-mailbox::raw-html', ['content' => $this->mailLog->body], function ($message) {
            if (! empty($this->mailLog->from) && is_array($this->mailLog->from)) {
                $from = array_key_first($this->mailLog->from);
                $name = $this->mailLog->from[$from];
                $message->from($from, $name);
            }

            $message->to($this->to)
                ->cc($this->cc)
                ->bcc($this->bcc)
                ->subject($this->mailLog->subject);

            if ($this->includeAttachments && $this->mailLog->attachments) {
                foreach ($this->mailLog->attachments as $attachment) {
                    $message->attachData(base64_decode($attachment['content']), $attachment['name'], ['mime' => $attachment['mime']]);
                }
            }
        });
    }
}
