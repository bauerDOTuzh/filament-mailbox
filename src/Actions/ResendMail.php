<?php

namespace Bauerdot\FilamentMailBox\Actions;

use Bauerdot\FilamentMailBox\Jobs\ResendMailJob;
use Bauerdot\FilamentMailBox\Models\MailLog;

class ResendMail
{
    /**
     * Resend the given MailLog entry to the provided recipients.
     *
     * @param MailLog $mailLog
     * @param array $to
     * @param array $cc
    * @param array $bcc
    * @param bool $includeAttachments
     */
    public function handle(MailLog $mailLog, array $to = [], array $cc = [], array $bcc = [], bool $includeAttachments = false): void
    {
        // If no recipients passed, fallback to original recipients
        $to = count($to) ? $to : array_keys((array) $mailLog->to ?: []);
        $cc = count($cc) ? $cc : array_keys((array) $mailLog->cc ?: []);
        $bcc = count($bcc) ? $bcc : array_keys((array) $mailLog->bcc ?: []);

        // Ensure at least one recipient
        if (empty($to) && empty($cc) && empty($bcc)) {
            return;
        }

        ResendMailJob::dispatch($mailLog, $to, $cc, $bcc, $includeAttachments);
    }
}