<?php

namespace Aecr\View\Page;

use App\Application;

class PanelPageView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct($app,AecrPageView::MENU_NONE,Application::EXPIRED_ALWAYS);
        //
        /*
        switch ($app->getAuthRole()) {
            case Application::ROLE_ADMIN:
                $suffix = '_admin';
                break;
            default:
                $suffix = '_user';
        }
        */
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}