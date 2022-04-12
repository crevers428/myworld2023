<?php

namespace Web;

use \App\Application;
use \WorldChamps\WorldChampsApplication;

ini_set('display_errors',1);
error_reporting(E_ALL);

spl_autoload_register(function ($className) {
        require_once '../' . preg_replace('/\\\\/','/',$className) . ".php";
    }
);

$myApp = new WorldChampsApplication(Application::ENV_AUTO,Application::ENV_AUTO);
$myApp->run();
