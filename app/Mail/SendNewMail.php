<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Campaign;

class SendNewMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $lead;
    public $order;
    public function __construct($_lead, $_order)
    {
        $this->lead = $_lead;
        $this->order = $_order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = Campaign::where('cod_campaign', $this->lead->cod_campaign)->first();
        $subject = $subject->title;
        return $this
        ->replyTo($this->lead->email)
        ->subject('Nuovo ordine: ' . $this->lead->id_invoice . ' dal funnel: ' . $subject)
        ->view('emails.message-request');
    }
}
