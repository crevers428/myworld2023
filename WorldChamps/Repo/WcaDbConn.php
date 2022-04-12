<?php

namespace Aecr\Repo;

use App\Pdo\PdoConnection;

class WcaDbConn extends PdoConnection
{
    public function __construct($app)
    {
        include '__private_wca__.inc'; // not include_once!
        parent::__construct($dsn,$user,$pass);
    }
}
