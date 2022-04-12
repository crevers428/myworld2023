<?php

namespace Aecr\Repo;

use App\Repo\Repo;
use Aecr\View;

class RepoWCA extends Repo
{
    private $metals = array('gold','silver','bronze');

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WcaDbConn($this->app));
        }
    }

    protected function sortMedals($a,$b) {
        $i = 0;
        while ($i<3) {
            if ($a[$this->metals[$i]]>$b[$this->metals[$i]]) {
                return -1;
            } elseif ($a[$this->metals[$i]]<$b[$this->metals[$i]]) {
                return 1;
            } else {
                $i++;
            }
        }
        return 0;
    }

    protected function getMedals()
    {
        $this->openConnection();
        $result = $this->DB->query('SELECT personId, personName as name, pos FROM Results '.
            'WHERE personCountryId="Spain" AND (roundTypeId="f" OR roundTypeId="c") AND pos<4 AND best>0');
        $aMedals = [];
        while ($row=$this->DB->fetch($result)) {
            if (!isset($aMedals[$row['personId']])) {
                $aMedals[$row['personId']] = array(
                    'name' => $row['name'],
                    'wcaID' => $row['personId'],
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0
                );
            }
            $aMedals[$row['personId']][$this->metals[$row['pos']-1]]++;
        }
        //
        uasort($aMedals,'Aecr\Repo\RepoWCA::sortMedals');
        //
        $count = 1;
        $lastMedals = array(
            'gold' => -1,
            'silver' => -1,
            'bronze' => -1
        );
        foreach ($aMedals as $key => &$row) {
            $x = 0;
            while ($x < 3 && $row[$this->metals[$x]] == $lastMedals[$this->metals[$x]]) $x++;
            if ($x != 3) {
                $ranking = $count;
                $lastMedals = $row;
            }
            $row['ranking'] = $ranking;
            $acc = 0;
            foreach($this->metals as $metal) {
                $acc += $row[$metal];
            }
            $row['total'] = $acc;
            $count++;
        }
        return $aMedals;
    }

    public function medals($view) {
        $this->openConnection();
        //
        $view->render(array(
                'medals' => $this->getMedals(),
            ));
    }
}