<?php

namespace App\View\Page;

use App\Application;
use App\View\HtmlView;

class CacheVersionView extends HtmlView
{
    const UTF8 = 'UTF-8';

    public function __construct(
        Application $app,
        $cacheExpiration = Application::EXPIRED_ALWAYS,
        $versionDependency = Application::VERSION_DEPENDENCY_NONE
    )
    {
        $app->setVersionDependency($versionDependency);
        $this->controlCacheIn($app,$cacheExpiration);
        parent::__construct($app);
    }

    public function renderView($vars = null)
    {
        /*
        if ($vars===null) $vars = array();
        $vars['link'] = $this->getLink();
        $vars['file'] = $this->getFile();
        */
        $this->embed($vars);
        $this->controlCacheOut($datetime);
        $this::__terminatePage($this->app,$this->content,false,$datetime);
        return $this->content;
    }

    public static function __terminatePage(Application $app,&$st,$cached,$datetime)
    {
        $terminators = array(
            'generation-header' => ($cached?'Cached':'Generated') . ' on ' . $datetime . ' in ~' . sprintf('%.2f',$app->elapsedTime()) . ' secs'
        );
        $app->addVersionTerminators($terminators);
        PageView::__embed($app, $st, $terminators);
        PageView::__terminate($st);
    }

    public function render($vars = null)
    {
        die($this->renderView($vars));
    }

    public function controlCacheIn(Application $app,$expiration)
    {
        if ($app->isProd())
            $app->controlCacheIn(get_called_class(),$expiration);
    }

    public function controlCacheOut(&$datetime)
    {
        //if (true) {
        if ($this->app->isProd()) {
            return $this->app->controlCacheOut($this->content,$datetime);
        } else {
            $datetime = $this->app->getProductionDatetime();
            return false;
        }
    }

    public static function deleteCacheFile(Application $app)
    {
        $app->deleteCacheFile(get_called_class());
    }
}