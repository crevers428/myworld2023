<?php
namespace WorldChamps\Email;

use App\Email\CustomEmail;

class Email extends CustomEmail
{
    const wcEmail = 'wc2019@speedcubing.org.au';
    const wcName = 'WCA World Championship 2019';

    public static function send($isProd, $to,$from,$replyTo,$bcc,$subject,$msg)
    {
        include '__private__.inc'; // not include_once!
        if (!$from && isset($noReplyEmail)) {
            $from = $noReplyEmail;
        }
        if (isset($subjectPrefix)) {
            $subject = $subjectPrefix.$subject;
        }
        if (!$isProd && isset($mailOnScreen) && $mailOnScreen) {
            echo sprintf('<hr>
To: <b>%s</b><br>
From: <b>%s</b><br>
ReplyTo: <b>%s</b><br>
Bcc: <b>%s</b><br>
Subject: <b>%s</b><br>
%s<hr>',
                $to,
                $from,
                $replyTo,
                $bcc,
                $subject,
                $msg
            );
            return true;
        } else {
            if (!$isProd && isset($developerEmail)) {
                $to = $developerEmail;
            }
            $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
            return parent::__send($to,$from,$replyTo,$bcc,$subject,$msg);
        }
    }
}
