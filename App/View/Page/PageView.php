<?php

namespace App\View\Page;

use App\Application;
//use App\View\HtmlView;

class PageView extends CacheVersionView
{
    protected $cssEntries = [];
    protected $jsEntries = [];

    public function __construct(
        Application $app,
        $cacheExpiration = Application::EXPIRED_ALWAYS,
        $versionDependency = Application::VERSION_DEPENDENCY_NONE,
        $title = 'TITLE',
        $charset = PageView::UTF8
    )
    {
        parent::__construct($app,$cacheExpiration,$versionDependency);
        //
        $html = '<head>' . PHP_EOL .
            '    <!-- Google Analytics -->' . PHP_EOL .
	                '    <script>' . PHP_EOL .
			            "    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
					                            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');" . PHP_EOL .
            "    ga('create', 'UA-132392220-1', 'auto');" . PHP_EOL .
            "    ga('send', 'pageview');" . PHP_EOL .
            '    </script>' . PHP_EOL .
            '    <!-- End Google Analytics -->' . PHP_EOL .
            '    <!-- {{generation-header|raw}} -->' . PHP_EOL .
            sprintf('    <meta http-equiv="Content-Type" content="text/html; charset=%s">',htmlspecialchars($charset)) . PHP_EOL .
            sprintf('    <title>%s</title>',htmlspecialchars($title)) . PHP_EOL;
        foreach ($this->cssEntries as $value) {
            $html .= sprintf('    <link rel="stylesheet"%s href="'.(preg_match('/^[http:|https:]/',$value['href'])?'':'{{file}}').'%s">',($value['media']?' media="'.$value['media'].'"':''),$value['href']) . PHP_EOL;
        }
        foreach ($this->jsEntries as $value) {
            $html .= sprintf('    <script src="'.(preg_match('/^[http:|https:]/',$value)?'':'{{file}}').'%s"></script>',$value) . PHP_EOL;
        }
        $html .= '    {{favicon|raw}}' . PHP_EOL;
        $html .= '    {{head|raw}}' . PHP_EOL .
            '</head>' . PHP_EOL;
        //
        $html .= '<body>' . PHP_EOL .
            '    {{postit|raw}}' . PHP_EOL .
            '    {{body|raw}}' . PHP_EOL .
            '</body>';
        //
        $this->embed(array(
            'html' => $html,
        ));
    }

    protected function addCss($cssName,$media=null)
    {
        $this->cssEntries[] = array(
            'href' => $cssName,
            'media' => $media
        );
        return $this;
    }

    protected function addJs ($jsName)
    {
        $this->jsEntries[] = $jsName;
        return $this;
    }

    public static function __terminatePage(Application $app,&$st,$cached,$datetime)
    {
        $postit = '';
        while ($app->renderPostIt($msg)) {
            $postit .= $msg . PHP_EOL;
        }
        $terminators = array(
            'generation-header' => ($cached?'Cached':'Generated') . ' on ' . $datetime . ' in ~' . sprintf('%.2f',$app->elapsedTime()) . ' secs',
            'postit' => $postit
        );
        $app->addVersionTerminators($terminators);
        PageView::__embed($app, $st, $terminators);
        PageView::__terminate($st);
    }
}
