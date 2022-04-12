<?php

namespace WorldChamps\View\Page;

use App\Application;

class SchedulePageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        $this->addCss("css/cubing-icons.css");

        parent::__construct(
            $app,
            WorldChampsPageView::MENU_SCHEDULE,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}