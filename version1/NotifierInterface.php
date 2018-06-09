<?php

namespace Notifier;

interface NotifierInterface
{
    /**
     * Method sends email with given $subject and $body
     * for all recipients from $addresses array
     *
     * @param string $subject
     * @param string $body
     * @param array $addresses
     *
     * @return mixed
     */
    public static function sendEmail($subject, $body, $addresses);
}
