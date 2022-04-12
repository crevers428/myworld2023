<?php

namespace Aecr\Repo;

use App\Repo\RepoAuth;

class AecrRepoAuth extends RepoAuth{

    protected $usersTableName = 'socios';
    protected $classCodeDoesNotExistView = '\Aecr\View\Page\CodeDoesNotExistView';
    protected $classExceptionDoesNotExistView = '\Aecr\View\Page\ExceptionDoesNotExistView';

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new AecrDbConn($this->app));
        }
    }
} 