<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 1:38 PM
 */
namespace App\Exception;

use App\View\View;

class Exception
{
    public function __construct(View $view, $vars = null)
    {
        $view->render($vars);
    }
}