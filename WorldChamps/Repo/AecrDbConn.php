<?php

namespace Aecr\Repo;

use App\Pdo\PdoConnection;

class AecrDbConn extends PdoConnection
{
    protected $lcTimeNames = 'en_AU';

    public function __construct($app)
    {
        include '__private__.inc'; // not include_once!
        parent::__construct($dsn,$user,$pass);
    }
}
