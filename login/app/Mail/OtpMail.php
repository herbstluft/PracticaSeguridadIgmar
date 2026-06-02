<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;

    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
    }

    /**
     * Obtener el sobre (envelope) del mensaje.
     */
    public function __envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación OTP',
        );
    }

    /**
     * Obtener la definición del contenido del mensaje.
     */
    public function __content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otpCode' => $this->otpCode,
            ],
        );
    }

    /**
     * Soporte para el método de construcción build heredado de configuraciones anteriores.
     */
    public function build()
    {
        return $this->subject('Tu código de verificación OTP')
                    ->view('emails.otp');
    }
}
