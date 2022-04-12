<?php

namespace Aecr\View\Page;

use App\Application;

class ACompPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_CAMPEONATOS,
            Application::EXPIRED_ALWAYS,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}