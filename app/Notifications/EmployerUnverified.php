<?php

namespace App\Notifications;

use App\Models\EmployerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a company its verification has been withdrawn, and that its ads have
 * come down with it.
 *
 * Quotes `employer_message` and never the internal reason. Staff write ticket
 * numbers and fraud suspicions in the latter, and mailing those to the company
 * they describe is the failure mode the two-field split exists to prevent.
 */
class EmployerUnverified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployerProfile $employerProfile,
        public ?string $employerMessage = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Verification withdrawn for :company', ['company' => $this->employerProfile->company_name]))
            ->line(__("We've withdrawn verification for :company. Your live jobs have been taken down and are waiting, and Talent Search and messaging are paused.", [
                'company' => $this->employerProfile->company_name,
            ]));

        if ($this->employerMessage !== null && trim($this->employerMessage) !== '') {
            $message->line($this->employerMessage);
        }

        return $message->line(__('Reply to your support conversation if you think this is a mistake.'));
    }
}
