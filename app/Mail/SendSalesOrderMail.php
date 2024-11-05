<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendSalesOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

     public $emailData;
     public $name;
 
    public function __construct($emailData,$name)
    {
        $this->emailData = $emailData;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Order Details')
        ->view('sales_order_mail')
        ->with([
            'emailData' => $this->emailData,
            'name' => $this->name,
        ]);;
    }
}
