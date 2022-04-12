<?php

namespace WorldChamps\Repo;

use App\Pdo\PdoConnection;

class WorldChampsDbConn extends PdoConnection
{
    protected $lcTimeNames = 'en_AU';

    public function __construct($app)
    {
        include '__private__.inc'; // not include_once!
        parent::__construct($dsn,$user,$pass);
    }
}
