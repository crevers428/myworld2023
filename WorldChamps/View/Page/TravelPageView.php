<?php

namespace WorldChamps\View\Page;

use App\Application;

class TravelPageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            WorldChampsPageView::MENU_TRAVEL,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}