<?php

namespace WorldChamps\View\Page;

use App\Application;

class FundayPageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            WorldChampsPageView::MENU_NONE,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}