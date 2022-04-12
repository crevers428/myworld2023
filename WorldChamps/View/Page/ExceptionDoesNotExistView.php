<?php

namespace WorldChamps\View\Page;

use App\Application;

class ExceptionDoesNotExistView extends WorldChampsPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            WorldChampsPageView::MENU_NONE,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_SOFT);
        //
        http_response_code(404);
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}