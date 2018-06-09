<?php

namespace Notifier;

class Notifier implements NotifierInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * Notifier constructor.
     */
    public function __construct()
    {
        $this->config = \Configuration::getInstance();
    }

    /**
     * Method sends notification by email channel
     *
     * @param string $subject
     * @param string $body
     * @param array $addresses
     *
     * @return mixed
     */
    public function sendEmail(string $subject, string $body, array $addresses)
    {
        $mailer = new phpmailer();
        $mailer->IsSMTP();
        $mailer->IsHTML(true);
        $mailer->CharSet = 'utf-8';
        $mailer->Host = $this->config['smtp_host'];
        $mailer->Subject = $subject;
        $mailer->Body = $body;

        foreach ($addresses as $address) {
            $mailer->AddAddress($address, '', 0);
        }

        return $mailer->Send();
    }
}
