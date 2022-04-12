<?php

namespace WorldChamps\View\Page;

use App\Application;

class AdminTshirtsPageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            WorldChampsPageView::MENU_NONE,
            Application::EXPIRED_ALWAYS,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}