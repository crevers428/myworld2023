<?php

namespace Aecr\View\Page;

use App\Application;

class SociosPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_SOCIOS,
            Application::EXPIRED_IN_5_MINUTES,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}