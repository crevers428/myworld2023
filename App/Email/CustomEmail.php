<?php

namespace App\Email;

class CustomEmail
{
    public static function __send($to,$from,$replyTo,$bcc,$subject,$msg)
    {
        $headers = sprintf("From: %s\r\n", $from);
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        if ($replyTo) $headers .= sprintf("Reply-To: %s\r\n", $replyTo);
        if ($bcc) $headers .= sprintf("Bcc: %s\r\n", $bcc);
        return mail($to,$subject,$msg,$headers);
    }
}