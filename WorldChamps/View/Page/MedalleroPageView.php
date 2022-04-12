<?php

namespace Aecr\View\Page;

use App\Application;

class MedalleroPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_MEDALLERO,
            Application::EXPIRED_IN_1_DAY,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}