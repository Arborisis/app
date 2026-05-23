<?php

namespace App\Mail\Transports;

use App\Services\Mail\MailServerService;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

class MailServerTransport implements TransportInterface
{
    private MailServerService $mailServer;

    public function __construct(MailServerService $mailServer)
    {
        $this->mailServer = $mailServer;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $html = $message->getHtmlBody();
        $text = $message->getTextBody();
        $subject = $message->getSubject();
        
        $toAddresses = [];
        foreach ($message->getTo() as $address) {
            $toAddresses[] = $address->getAddress();
        }
        
        $to = implode(',', $toAddresses);
        
        try {
            $result = $this->mailServer->send($to, $subject, $html, $text);
            
            return new SentMessage($message, $envelope ?? new Envelope(
                new Address('contact@arborisis.com'),
                $message->getTo()
            ));
        } catch (\Exception $e) {
            throw new \Symfony\Component\Mailer\Exception\TransportException(
                'Failed to send via Mail Server: ' . $e->getMessage()
            );
        }
    }

    public function __toString(): string
    {
        return 'mailserver';
    }
}
