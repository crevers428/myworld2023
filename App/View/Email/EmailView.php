<?php

namespace App\View\Email;

use App\Application;
use App\View\Page\CacheVersionView;

class EmailView extends CacheVersionView
{
    public function __construct(
        Application $app,
        $cacheExpiration = Application::EXPIRED_ALWAYS,
        $versionDependency = Application::VERSION_DEPENDENCY_NONE,
        $charset = CacheVersionView::UTF8)
    {
        parent::__construct($app,$cacheExpiration,$versionDependency);
        $this->embed(array(
            'html' => '<head>' . PHP_EOL .
                '    <meta charset="'.$charset.'" />' . PHP_EOL .
                '    {{head|raw}}' .
                '</head>' . PHP_EOL .
                '<body>' . PHP_EOL .
                '    {{body|raw}}' . PHP_EOL .
                '</body>'
        ));
    }

    protected static function getVarValue(Application $app,&$vars,$label,$suffix, $for_index)
    {
	$useFullPath = true;
        switch($label) {
            case 'link': // todo - debería ser sys_link o algo más reservado
                return $app->getWebUri('/',$useFullPath);
            case 'file': // todo - debería ser sys_file o algo más reservado
                return $app->getFileUri('',$useFullPath);
            default:
                return parent::getVarValue($app,$vars,$label,$suffix, $for_index);
        }
    }
}
