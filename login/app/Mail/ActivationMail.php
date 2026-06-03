<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $activationUrl;

    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct(User $user, string $activationUrl)
    {
        $this->user = $user;
        $this->activationUrl = $activationUrl;
    }

    /**
     * Obtener el sobre (envelope) del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activa tu cuenta de seguridad',
        );
    }

    /**
     * Obtener la definición del contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.activation',
            with: [
                'user' => $this->user,
                'activationUrl' => $this->activationUrl,
            ],
        );
    }

    /**
     * Soporte para el método de construcción build heredado de configuraciones anteriores.
     */
    public function build()
    {
        return $this->subject('Activa tu cuenta de seguridad')
                    ->view('emails.activation');
    }
}
