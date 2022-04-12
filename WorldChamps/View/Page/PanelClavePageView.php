<?php

namespace Aecr\View\Page;

use App\Application;

class PanelClavePageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct($app,AecrPageView::MENU_NONE,Application::EXPIRED_ALWAYS);
        //
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}