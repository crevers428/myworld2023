<?php

namespace WorldChamps\View\Page;

use App\Application;

class ContactPageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            WorldChampsPageView::MENU_CONTACT,
            Application::EXPIRED_ALWAYS,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}