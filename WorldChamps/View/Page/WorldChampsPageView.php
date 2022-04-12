<?php

namespace WorldChamps\View\Page;

use App\Application;
use Bootstrap\View\Page\BootstrapPageView;

class WorldChampsPageView extends BootstrapPageView
{
    const MENU_NONE = 0;
    const MENU_NEWS = 1;
    const MENU_EVENTS = 2;
    const MENU_SCHEDULE = 3;
    const MENU_REGISTRATION = 4;
    const MENU_COMPETITORS = 5;
    const MENU_TRAVEL = 6;
    const MENU_LODGING = 7;
    const MENU_TICKETS = 8;
    const MENU_COLLABORATORS = 9;
    const MENU_CONTACT = 10;
    const MENU_FAQ = 11;
    const MENU_KOALA = 12;
    const MENU_WARMUP = 13;
    const MENU_NCUP = 14;

    public function __construct(
        $app,
        $menuItem,
        $cacheExpiration = Application::EXPIRED_ALWAYS,
        $versionDependency = Application::VERSION_DEPENDENCY_NONE,
        $title='WCA World Championship 2019')
    {
        parent::__construct($app,$cacheExpiration,$versionDependency,$title);
        //
        /*
        $app->addVersionEmbedders('language',$embedders);
        $this->embed(array(
            'body' => $this->getHtmlFromFile(__CLASS__)
        ));
        $this->embed(array(
            "selItem" => $menuItem
        ));
        */
        $this->embed(array(
            'postit' => "",
            'favicon' => <<< TEXT

    <link rel="apple-touch-icon" sizes="57x57" href="{{file}}apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="{{file}}apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="{{file}}apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="{{file}}apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="{{file}}apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="{{file}}apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="{{file}}apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="{{file}}apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="{{file}}apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="{{file}}android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="{{file}}favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="{{file}}favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="{{file}}favicon-16x16.png">
    <link rel="manifest" href="{{file}}manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{file}}ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
TEXT
            ,
        'body' => $this->getHtmlFromFile(__CLASS__,Application::VERSION_DEPENDENCY_SOFT)
        ));
        $embedders = array(
            "selItem" => $menuItem,
            "topOffset" => -100
        );
        $app->addVersionEmbedders('language',$embedders);
        $this->embed($embedders);
    }
}

