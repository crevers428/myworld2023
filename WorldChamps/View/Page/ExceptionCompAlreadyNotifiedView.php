<?php

namespace Aecr\View\Page;

use App\Application;

class ExceptionCompAlreadyNotifiedView extends AecrPageView
{
    public function __construct($app)
    {
        parent::__construct(
            $app,
            AecrPageView::MENU_NONE,
            Application::EXPIRED_NEVER,
            Application::VERSION_DEPENDENCY_NONE);
        //
        http_response_code(404);
        $this->embed(array(
                'body' => $this->getHtmlFromFile(__CLASS__)
            ));
    }
}