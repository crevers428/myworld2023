<?php

namespace Aecr\View\Page;

use App\Application;

class EventosPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_EVENTOS,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}