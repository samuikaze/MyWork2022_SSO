<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 寄件者郵件地址
     *
     * @var string
     */
    protected $send_address;

    /**
     * 寄件者名稱
     *
     * @var string
     */
    protected $send_name;

    /**
     * 重設密碼的權杖
     *
     * @var string
     */
    protected $reset_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $token)
    {
        $this->send_address = env('MAIL_FROM_ADDRESS', 'example@example.com');
        $this->send_name = env('MAIL_FROM_NAME');
        $this->reset_token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->processRequiredData();

        return $this
            ->from($this->send_address, $this->send_name)
            ->view('mails.forget_password', compact('data'));
    }

    /**
     * 處理所需的資料
     *
     * @return array
     */
    protected function processRequiredData(): array
    {
        ['apply_date' => $apply_at] = decrypt($this->reset_token);

        $data = [
            'uri' => 'https://smkzsvc.ddns.net:29511/reset/password?token='.$this->reset_token,
            'apply_at' => $apply_at,
        ];

        return $data;
    }
}
