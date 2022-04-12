<?php

namespace WorldChamps\View\Email;

use App\View\Email\EmailView;

class ContactEmailView extends EmailView
{
    public function __construct($app,$language)
    {
        parent::__construct($app);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__."_$language")
            ));
    }
}