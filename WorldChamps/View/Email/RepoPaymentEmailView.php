<?php

namespace WorldChamps\View\Email;

use App\View\Email\EmailView;

class RepoPaymentEmailView extends EmailView
{
    public function __construct($app)
    {
        parent::__construct($app);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}
