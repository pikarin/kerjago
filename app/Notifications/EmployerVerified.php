<?php

namespace App\Notifications;

use App\Models\EmployerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a company it has cleared verification.
 *
 * By email rather than in-app alone: the employer submitted an ad, saw
 * "Pending", and closed the tab. Nothing brings them back on their own.
 */
class EmployerVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmployerProfile $employerProfile) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':company is verified', ['company' => $this->employerProfile->company_name]))
            ->greeting(__('Good news.'))
            ->line(__(':company has been verified. Any job you had waiting is going live now, and Talent Search and messaging are open.', [
                'company' => $this->employerProfile->company_name,
            ]))
            ->action(__('Go to your jobs'), route('employer.jobs.index'));
    }
}
