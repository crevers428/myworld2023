<?php

namespace Aecr\Repo;

use Aecr\Lib\BasicFuncs;
use App\Repo\Repo;
use Aecr\Lib\Schedule;
use Aecr\View\Page;
use Aecr\Lib;
use App\Application;

class Event {

    public $id;
    public $name;
    public $selected = false;

    public function __construct($id,$name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}

class Socio {

    public $id;
    public $nombre;
    public $email;

    public function __construct($id,$nombre,$email)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
    }
}

class RepoComps extends Repo
{
    const CUBECOMPS_UPLOAD_DIR = '../../comps/uploads/';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->addPermissions(Application::ROLE_ADMIN, 'id','nombre','fecha_comienzo', 'fecha_final', 'lugar', 'precio', 'direccion', 'lat', 'lng', 'alojamiento', 'patrocinadores', 'cubecompsId', 'limite');
    }

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new AecrDbConn($this->app));
        }
    }

    public function getCompeticionById($id) {
        $this->openConnection();
        $result = $this->DB->query('SELECT * FROM competiciones WHERE id=?',array($id));
        if (!count($result)) return null;
        return $result[0];
    }

    public function setNotified($id) {
        $this->openConnection();
        $this->DB->query('UPDATE competiciones SET notificada=1 WHERE id=?',array($id));
    }

    private function addDatesToStr(&$result)
    {
        foreach($result as &$row) {
            $row['fechas'] = BasicFuncs::datesToStr($row["fecha_comienzo"],$row["fecha_final"]);
        }
    }

    public function getNextComps()
    {
        $this->openConnection();
        $result = $this->DB->query('SELECT * FROM competiciones WHERE CURDATE()<=fecha_final AND notificada ORDER BY fecha_comienzo');
        $this->addDatesToStr($result);
        return $result;
    }

    public function getPastComps()
    {
        $this->openConnection();
        $result = $this->DB->query('SELECT * FROM competiciones WHERE CURDATE()>fecha_final AND notificada ORDER BY fecha_comienzo DESC');
        $this->addDatesToStr($result);
        return $result;
    }

    public function getEvents()
    {
        $this->openConnection();
        return $this->DB->query('SELECT * FROM events ORDER BY id');
    }

    public function getEventsJSON()
    {
        $events = $this->getEvents();
        $objects = array();
        foreach($events as $event) {
            $objects[] = new Event($event['abbr'],$event['name']);
        }
        return json_encode($objects,JSON_HEX_APOS);
    }

    public function getPersons()
    {
        $this->openConnection();
        return $this->DB->query('SELECT id, nombre, email FROM socios ORDER BY nombre');
    }

    public function getPersonsJSON()
    {
        $socios = $this->getPersons();
        $objects = array();
        foreach($socios as $socio) {
            $objects[] = new Socio($socio['id'],trim($socio['nombre']),trim($socio['email']));
        }
        return json_encode($objects,JSON_HEX_APOS);
    }

    public function getCompetitionEvents($id)
    {
        $this->openConnection();
        return $this->DB->query(
            'SELECT events.abbr, events.name FROM events_comps JOIN events ON events.abbr = event_abbr WHERE comp_id=? ORDER BY events.id',array($id)
        );
    }

    public function getCompetitionEventsJSON($id)
    {
        $result = $this->getCompetitionEvents($id);
        $array = array();
        foreach($result as $abbr) {
            $array[] = $abbr['abbr'];
        }
        return json_encode($array);
    }

    public function getCompetitionOrganizers($id)
    {
        $this->openConnection();
        return $this->DB->query('SELECT name, email FROM organizers_comps WHERE comp_id=? ORDER BY name',array($id));
    }

    public function getCompetitionOrganizersJSON($id)
    {
        $result = $this->getCompetitionOrganizers($id);
        $array = array();
        foreach($result as $organizer) {
            $array[] = array($organizer['name'],$organizer['email']);
        }
        return json_encode($array);
    }

    public function getCompetitionDelegates($id)
    {
        $this->openConnection();
        return $this->DB->query('SELECT name, email FROM delegates_comps WHERE comp_id=? ORDER BY name',array($id));
    }

    public function getCompetitionDelegatesJSON($id)
    {
        $result = $this->getCompetitionDelegates($id);
        $array = array();
        foreach($result as $delegate) {
            $array[] = array($delegate['name'],$delegate['email']);
        }
        return json_encode($array);
    }

    private function schNextLine($fh)
    {
        $line = fgets($fh);
        return trim($line);
    }

    private function fetch(&$l)
    {
        $aux = $l;
        $p = strpos($aux,",");
        if ($p!==false)
        {
            $l = substr($aux,$p+1);
            return substr($aux,0,$p);
        }
        else
        {
            $l = "";
            return $aux;
        }
    }

    public function campeonatos($view)
    {
        $this->openConnection();
        $next = $this->getNextComps();
        $past = $this->getPastComps();
        $view->render(array(
            'next-competitions' => $next,
            'past-competitions' => $past
        ));
    }

    function htmlSchedule($ccId)
    {
        $fn = null;
        $fn = $this::CUBECOMPS_UPLOAD_DIR . "sch_$ccId.txt";
        if (!file_exists($fn)) {
            return '';
        }
        //
        if (!$this->app->isProd()) {
            date_default_timezone_set('Australia/Melbourne');
        }
        $html = '<div class="col-xs-12" style="height:100%;overflow-x:auto;">';
        $categories = [];
        $r = $this->getEvents();
        while ($row=$this->DB->fetch($r)) {
            $categories[$row['abbr']] = $row['name'];
        }
        //
        /* clean up
        $r = strict_query("SELECT * FROM $eventstable JOIN categories ON $eventstable.id=categories.id");
        while ($row=cased_mysql_fetch_array($r))
        {
            $events[_RX][$row["abbr"]] = 0;
            $events[_ID][$row["abbr"]] = $row["id"];
            $x = 1;
            while ($x <= 4 && $row["r$x"."_open"]) $x++;
            $events[_RTOP][$row["abbr"]] = $x-1;
        }
        */
        //
        $fh = $fn ? fopen($fn,'r') : null;
        $ver = $this->schNextLine($fh);
        if ($ver=='01')
        {
            $IE = (preg_match("/msie/i",$_SERVER["HTTP_USER_AGENT"]) || preg_match("/internet explorer/i",$_SERVER["HTTP_USER_AGENT"]));
            //
            $this->schNextLine($fh); // drop time zone
            $this->schNextLine($fh); // drop GMT+x
            $pm = intval($this->schNextLine($fh),10);
            $ox = 0;
            $line = $this->schNextLine($fh);
            while ($line) {
                $day = strtotime($line);
                $html .= "<span style='position:absolute;top:".$ox."px;'><b>".date("l - F jS, Y",$day)."</b></span> <span style='position:absolute;top:".($ox+19)."px;width:300px;".($IE?"border-top:5px solid #444444;":"background-color:#444444;height:5px;")."'></span><p></p>\r\n";
                $ox += 36;
                $sch = new Schedule($categories);
                while (($line = $this->schNextLine($fh)) && !strtotime($line))
                {
                    $start = $this->fetch($line);
                    $end = $this->fetch($line);
                    $evt = $this->fetch($line);
                    $altEvt = $this->fetch($line);
                    $rnd = $this->fetch($line);
                    $comment = $this->fetch($line);
                    $sch->addRound($start,$end,$evt,$altEvt,$rnd,$comment);
                }
                //
                /*
                $openr = strict_query("SELECT DISTINCT CONCAT(abbr,'_',round) AS code FROM $timestable JOIN categories ON id=cat_id");
                $resultRnds = array();
                while ($rowor=cased_mysql_fetch_array($openr)) $resultRnds[$rowor["code"]] = true;
                //
                $a1 = getdate($timethere);
                $a2 = getdate($day);
                if ($a1["year"]==$a2["year"] && $a1["mon"]==$a2["mon"] && $a1["mday"]==$a2["mday"])
                {
                    $position = $a1["hours"]*12 + floor($a1["minutes"] / 5);
                    echo $sch->out($ox,$pm,$events,$resultRnds,$position);
                }
                else
                {
                    $dummy = null;
                    echo $sch->out($ox,$pm,$events,$resultRnds,$dummy);
                }
                $ox += 36;
                */
                $html .= $sch->out($ox,$pm,$IE);
                $sch = null;
                $ox += 36;
            }
            $html = "<h1>Horario</h1>\r\n" .
                //echo "\r\n<DIV id=SCH_container style='position:relative;height:95%;overflow-y:auto;font-weight:normal;'>\r\n";
                "<div style='position:relative;height:".$ox."px'>\r\n" .
                $html .
                "</div>\r\n";
        }
        if ($fh) {
            fclose($fh);
        }
        $html .= '</div>';
        return $html;
    }

    public function showComp($id,$view)
    {
        $result = $this->getCompeticionById($id);
        if (!$result) {
            $exception = new Page\ExceptionDoesNotExistView($this->app);
            $exception->render();
        } else {
            $arr = array(
                'id' => $id,
                'nombre' => $result["nombre"],
                'lugar' => $result["lugar"],
                'fechas' => BasicFuncs::datesToStr($result["fecha_comienzo"],$result["fecha_final"]),
                'precio' => $result["precio"],
                'organizadores' => $this->getCompetitionOrganizers($id),
                'delegados' => $this->getCompetitionDelegates($id),
                'limite' => $result['limite'],
                'pruebas' => $this->getCompetitionEvents($id),
                'cubecompsId' => $result["cubecompsId"],
                'horario' => $this->htmlSchedule($result["cubecompsId"]),
                'direccion' => $result["direccion"],
                'lat' => $result['lat'],
                'lng' => $result['lng'],
                'alojamiento' => $result["alojamiento"],
                'patrocinadores' => $result["patrocinadores"]
            );
            if (!$result["cubecompsId"] || !$this->app->isProd()) {
                $arr['banner_exists'] = false;
            } else {
                $arr['banner_exists'] = file_exists($this::CUBECOMPS_UPLOAD_DIR.'ban_'.$result["cubecompsId"].'.gif');
            }
            $view->render($arr);
        }
    }

    public function getCompeticionesTable()
    {
        $this->openConnection();
        return $this->DB->query('SELECT * FROM competiciones ORDER BY fecha_comienzo DESC');
    }

    // these three only admins

    protected function deleteCompetitionEvents($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM events_comps WHERE comp_id=?',array($id));
    }

    protected function insertCompetitionEvents($id,$json)
    {
        $this->openConnection();
        $abbrs = json_decode($json);
        foreach($abbrs as $abbr) {
            $this->DB->query('INSERT INTO events_comps SET comp_id=?, event_abbr=?',array($id,$abbr));
        }
    }

    protected function updateCompetitionEvents($oldId,$newId,$json)
    {
        $this->deleteCompetitionEvents($oldId);
        $this->insertCompetitionEvents($newId,$json);
    }

    protected function deleteCompetitionOrganizers($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM organizers_comps WHERE comp_id=?',array($id));
    }

    protected function insertCompetitionOrganizers($id,$json)
    {
        $this->openConnection();
        $table = json_decode($json);
        foreach($table as $row) {
            $this->DB->query('INSERT INTO organizers_comps SET comp_id=?, name=?, email=?',array($id,$row[0],$row[1]));
        }
    }

    protected function updateCompetitionOrganizers($oldId,$newId,$json)
    {
        $this->deleteCompetitionOrganizers($oldId);
        $this->insertCompetitionOrganizers($newId,$json);
    }

    protected function deleteCompetitionDelegates($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM delegates_comps WHERE comp_id=?',array($id));
    }

    protected function insertCompetitionDelegates($id,$json)
    {
        $this->openConnection();
        $table = json_decode($json);
        foreach($table as $row) {
            $this->DB->query('INSERT INTO delegates_comps SET comp_id=?, name=?, email=?',array($id,$row[0],$row[1]));
        }
    }

    protected function updateCompetitionDelegates($oldId,$newId,$json)
    {
        $this->deleteCompetitionDelegates($oldId);
        $this->insertCompetitionDelegates($newId,$json);
    }

    public function addCompeticion($values)
    {
        $this->insert('competiciones',$values);
        $this->insertCompetitionEvents($values['id'],$values['pruebas']);
        $this->insertCompetitionOrganizers($values['id'],$values['organizadores']);
        $this->insertCompetitionDelegates($values['id'],$values['delegados']);
    }

    public function editCompeticion($oldId,$values)
    {
        $this->update('competiciones','id',$oldId,$values);
        $this->updateCompetitionEvents($oldId,$values['id'],$values['pruebas']);
        $this->updateCompetitionOrganizers($oldId,$values['id'],$values['organizadores']);
        $this->updateCompetitionDelegates($oldId,$values['id'],$values['delegados']);
    }

    public function deleteCompeticion($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM competiciones WHERE id=?',array($id));
        $this->deleteCompetitionEvents($id);
        $this->deleteCompetitionOrganizers($id);
        $this->deleteCompetitionDelegates($id);
    }

}
