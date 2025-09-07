<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class FooterEmail extends Mailable
{
    use Queueable, SerializesModels;
    protected $data = [];
    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data; 
    }
    public function build(){
        // return $this->from(env('MAIL_FROM_ADDRESS','smarttrashku@gmail.com'),env('APP_NAME','Smart TrashKu'))->subject($this->data['subject'])->view('emails.forgotPassForm')->with('token',$this->data);
        // return $this->view('email.forgotPassForm');
        return $this->view('email.forgotPassForm')
        ->with(['email' => $this->data['email'],'code'=>$this->data['code'],'link'=>$this->data['link']])
        ->from(env('MAIL_FROM_ADDRESS', 'smarttrashku@gmail.com'), env('APP_NAME', 'Smart TrashKu'));
    }
}