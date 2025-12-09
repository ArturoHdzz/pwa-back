<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
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
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verifica tu Correo')->html("
            <div style='text-align: center;'>
                <h1>Verifica tu cuenta</h1>
                <p>Tu código de activación es:</p>
                <h2 style='background: #eee; padding: 10px; display: inline-block;'>{$this->code}</h2>
            </div>
        ");
    }
}
