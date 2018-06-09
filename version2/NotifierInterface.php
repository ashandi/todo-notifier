<?php

namespace Notifier;

interface NotifierInterface
{
    /**
     * Method sends notification by email channel
     *
     * @param string $subject
     * @param string $body
     * @param array $addresses
     *
     * @return mixed
     */
    public function sendEmail(string $subject, string $body, array $addresses);
}
