<?php

namespace Bootstrap\View\Page;

use App\Application;
use App\View\Page\PageView;

class BootstrapPageView extends PageView
{
    public function __construct(
        Application $app,
        $cacheExpiration = Application::EXPIRED_ALWAYS,
        $versionDependency = Application::VERSION_DEPENDENCY_NONE,
        $title='')
    {
        $this->addCss('css/bootstrap.min.css');
        $this->addCss('css/animations.css');
        $this->addCss('css/wc_01.css');
        $this->addCss("https://fonts.googleapis.com/css?family=Montserrat:300,400,800");

        $this->addJs('js/jquery-2.1.4.min.js');
        $this->addJs('js/bootstrap.min.js');

        parent::__construct($app,$cacheExpiration,$versionDependency,$title);
        //
        $head = '<meta name="viewport" CONTENT="width=device-width, initial-scale=1">' . PHP_EOL .
            '    {{head|raw}}';
        $body = '{{body|raw}}' . PHP_EOL .
            '<script src="{{file}}js/css3-animate-it.js"></script>';
        $this->embed(array(
            'head' => $head,
            'body' => $body
        ));
    }
}
