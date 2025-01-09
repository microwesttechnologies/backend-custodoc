<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;


    /**
     * Create a new message instance.
     */
    public function __construct($token)
    {
        $this->resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token;
    }

    public function build()
    {
        return $this->subject('Recuperar contraseña')
            ->view('emails.password_reset')
            ->with([
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
