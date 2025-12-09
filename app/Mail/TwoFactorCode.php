<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCode extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    /**
     * Create a new message instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    // Solo usamos build. Borra content() y envelope() para evitar conflictos.
    public function build()
    {
        return $this->subject('Tu Código de Verificación')->html("
            <h1>Tu código es: {$this->code}</h1>
            <p>Este código expira en 10 minutos.</p>
        ");
    }
}
