<?php

namespace WorldChamps\View\Page;

use App\Application;

class MyWorldsPageView extends WorldChampsPageView
{
    public function __construct($app)
    {
        $this->addCss("css/fullcalendar.min.css");
        $this->addCss("css/fullcalendar.print.css","print");
        $this->addCss("css/cubing-icons.css");
        $this->addCss("css/calendar.css");
        $this->addJs("js/moment.min.js");

        parent::__construct(
            $app,
            WorldChampsPageView::MENU_NONE,
            Application::EXPIRED_ALWAYS,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}