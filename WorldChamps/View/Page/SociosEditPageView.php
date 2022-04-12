<?php

namespace Aecr\View\Page;

use App\Application;

class SociosEditPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_NONE,
            Application::EXPIRED_ALWAYS,
            Application::VERSION_DEPENDENCY_NONE
        );
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}