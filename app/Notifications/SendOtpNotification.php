<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $otp;
    protected string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp, string $type = 'registration')
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'registration' 
            ? 'Verify Your Email Address' 
            : 'Reset Your Password';

        $greeting = $this->type === 'registration'
            ? 'Welcome to ' . config('app.name') . '!'
            : 'Password Reset Request';

        $line1 = $this->type === 'registration'
            ? 'Thank you for registering with us. Please use the following OTP to verify your email address:'
            : 'You are receiving this email because we received a password reset request for your account. Please use the following OTP:';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line('**' . $this->otp . '**')
            ->line('This OTP will expire in 10 minutes.')
            ->line($this->type === 'registration' 
                ? 'If your account is not verified within 30 minutes, it will be automatically deleted.' 
                : 'If you did not request a password reset, no further action is required.')
            ->salutation('Regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'type' => $this->type,
        ];
    }
}
