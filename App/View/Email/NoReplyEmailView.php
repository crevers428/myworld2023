<?php
/**
 * User: luis
 * Date: 6/7/13
 */
namespace App\View\Email;

class NoReplyEmailView extends EmailView
{
    protected function renderBody()
    {
        return '%s
<hr><span style="font-size: 8pt;">
Esto es un mensaje automático; no lo respondas porque no recibiremos tu respuesta
</span>
';
    }
}