<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class EventBookingMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $data = [];
    public function __construct($data)
    {
        $this->data = $data; 
    }
    public function build(){
        return $this->view('mails.eventBooking')
        ->with(['email' => $this->data['email'],'name' => $this->data['name'],'event_id' => $this->data['event_id']])
        ->from(env('MAIL_FROM_ADDRESS', 'unievents@gmail.com'), env('APP_NAME', 'Uni Events'));
    }
}