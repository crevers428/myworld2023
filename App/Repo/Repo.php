<?php

namespace App\Repo;

use App\Application;
use App\Pdo\PdoConnection;

abstract class Repo
{
    const DATE_SQL_FORMAT = 'Y-m-d H:i:s';

    protected $app;
    protected $DB;
    protected $opened;

    protected $minRoleByColumn = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function __openConnection($DB)
    {
        $this->DB = $DB;
        $this->opened = true;
    }

    protected static function getDateTime($interval = null)
    {
        $date = new \DateTime();
        if ($interval) {
            $date->add(new \DateInterval($interval));
        }
        return $date->format(Repo::DATE_SQL_FORMAT);
    }

    protected function addPermissions()
    {
        $nParams = func_num_args();
        if ($nParams < 2)
            die('addPermissions() - insufficient number of params');
        $params = func_get_args();
        $role = $params[0];
        if (!is_int($role) || $role < Application::ROLE_USER || $role > Application::ROLE_SUPER_ADMIN)
            die('addPermissions() - invalid role');
        if ($this->minRoleByColumn===null) $this->minRoleByColumn = array();
        for ($i=1;$i<$nParams;$i++) {
            $this->minRoleByColumn[$params[$i]] = $role;
        }
    }

    protected function permitted($column)
    {
        if (!$this->minRoleByColumn) die ('Unsecure Repo - calling addPermissions() is mandatory');
        if (!array_key_exists($column,$this->minRoleByColumn)) {
            return false;
        }
        return ($this->app->getAuthRole() >= $this->minRoleByColumn[$column]);
    }

    protected function getSETSection($params, &$query,&$values)
    {
        $query = '';
        $values = array();
        foreach ($params as $key => $param) {
            if ($this->permitted($key)) {
                if ($query) $query .= ', ';
                $query .= $key . '=?';
                $values[] = $param;
            }
        }
    }

    protected function update($tableName,$uniqueIndexName,$uniqueIndexValue,$params)
    {
        $this->getSETSection($params, $query,$values);
        $query = 'UPDATE ' . $tableName . ' SET ' . $query . ' WHERE ' . $uniqueIndexName . '=?';
        $values[] = $uniqueIndexValue;
        $this->openConnection();
        return $this->DB->query($query,$values);
    }

    protected function insert($tableName,$params)
    {
        $this->getSETSection($params, $query,$values);
        $query = 'INSERT ' . $tableName . ' SET ' . $query;
        $this->openConnection();
        return $this->DB->query($query,$values);
    }

    public function isUnique($tableName,$columnName,$value,$currentId)
    {
        $this->openConnection();
        $query = 'SELECT id FROM ' . $tableName . ' WHERE ' . $columnName . '=?';
        $result = $this->DB->query($query,array($value));
        /*
        echo "<p>$query</p>";
        echo "<p>$currentId</p>";
        print_r($result);
        die();
        */
        return (!count($result) || ( count($result) == 1 && $result[0]['id'] == $currentId ));
    }

    public function results_to_array($results,$key)
    {
        $return = array();
        foreach ($results as $row) {
            $return[] = $row[$key];
        }
        return $return;
    }
}