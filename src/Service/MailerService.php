<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    private MailerInterface $mailer;
    private string $mailer_reply_to;
    private string $mailer_sender;

    public function __construct(
        MailerInterface $mailer,
                        $mailer_reply_to,
                        $mailer_sender,
    )
    {
        $this->mailer = $mailer;
        $this->mailer_reply_to = $mailer_reply_to;
        $this->mailer_sender = $mailer_sender;
    }


    public function sendEmail(
        string $to,
        string $subject,
        string $body
    ): array
    {
        $email = (new Email())
            ->from($this->mailer_sender)
            ->to($to)
            ->replyTo($this->mailer_reply_to)
            ->priority(Email::PRIORITY_NORMAL)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailer->send($email);

            return array('status' => 'ok');
        } catch (TransportExceptionInterface $e) {

            return array('status' => 'ko', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString());
        }
    }
}
