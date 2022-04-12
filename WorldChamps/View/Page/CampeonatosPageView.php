<?php

namespace Aecr\View\Page;

use App\Application;

class CampeonatosPageView extends AecrPageView
{
    public function __construct($app)
    {
        $this->addJs("https://maps.googleapis.com/maps/api/js");
        parent::__construct(
            $app,
            AecrPageView::MENU_CAMPEONATOS,
            Application::EXPIRED_IN_5_MINUTES,
            Application::VERSION_DEPENDENCY_NONE);
        //
        $this->embed(array(
            'body' => $this->getHtmlFromFile(__CLASS__)
        ));
    }
}