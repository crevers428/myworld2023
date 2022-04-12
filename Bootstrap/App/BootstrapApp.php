<?php

namespace Bootstrap\App;

use App\Application;

class BootstrapApp extends Application
{
    public function renderPostIt(&$layout)
    {
        if ($return = $this->getPostIt($type,$msg)) {
            switch($type) {
                case Application::POSTIT_ERROR:
                    $class = 'danger';
                    $label = '¡Error!';
                    break;
                case Application::POSTIT_SUCCESS:
                    $class = 'success';
                    $label = '¡Genial!';
                    break;
                default:
                    $class = 'info';
                    $label = 'Info';
                    break;
            }
            $layout = sprintf(
                '<div class="alert alert-%s">'.
                '<button type="button" class="close" data-dismiss="alert">'.
                '×'.
                '</button>'.
                '<label class="label label-warning">%s</label>'.
                ' %s'.
                '</div>',
                $class,
                $label,
                $msg
            );
        }
        return $return;
    }
} 