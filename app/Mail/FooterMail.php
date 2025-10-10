<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class FooterMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $data = [];
    public function __construct($data)
    {
        $this->data = $data; 
    }
    public function build(){
        return $this->view('mails.footerMail')
        ->with(['email' => $this->data['email']])
        ->from(env('MAIL_FROM_ADDRESS', 'unievents@gmail.com'), env('APP_NAME', 'Uni Events'));
    }
}