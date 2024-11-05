<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendQuoteDetailsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;
    public $name;
    // public $filePath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailData, $name)
    {
        $this->emailData = $emailData;
        $this->name = $name;
        // $this->filePath = $filePath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Quote Details')
            ->view('quote_mail')
            ->with([
                'emailData' => $this->emailData,
                'name' => $this->name,
                // 'filePath' => $this->filePath,
            ]);


        // if ($this->filePath) {
        //     $email->attach($this->filePath, [
        //         'as' => 'supporting_document.' . pathinfo($this->filePath, PATHINFO_EXTENSION),
        //         'mime' => mime_content_type($this->filePath),
        //     ]);
        // }
    }
}
