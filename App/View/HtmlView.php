<?php

namespace App\View;

use App\Application;

class HtmlView extends View
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->embed(array(
            'view' => '<!doctype html>'.PHP_EOL.'<html>'.PHP_EOL.'    {{html|raw}}'.PHP_EOL.'</html>'
        ));
    }

    /*
    public function renderView($vars = null)
    {
        if ($vars===null) $vars = array();
        $vars['link'] = $this->getLink();
        $vars['file'] = $this->getFile();
        return parent::renderView($vars);
    }
    */

    protected function getHtmlFromFile($class=null,$versionDependency=null)
    {
        //die(get_called_class().'<br>'.get_class($this).'<br>'.__CLASS__.'<br>'.$class);
        if (!$class) $class = get_class($this);
        $arr = explode('\\',$class);
        $fileName = '..';
        foreach ($arr as $value) {
            $fileName .= '/'.$value;
        }
        $fileName .= $this->app->versionToStr($versionDependency);
        $fileName .= '.html';
        if (!file_exists($fileName)) {
            die("File '$fileName' not found");
        } else {
            return file_get_contents($fileName);
        }
    }
}