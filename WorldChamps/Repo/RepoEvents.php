<?php

namespace WorldChamps\Repo;

use App\Application;
use App\Repo\Repo;
use WorldChamps\View\Page;

class UserEvent
{
    public $id;
    public $name;
    public $signed_up;
    public $scramble;
    public $no_scramble;
    public $warm_up;

    public function __construct($id,$name,$scrambleable,$warmable,$signed_up,$scramble,$no_scramble,$warm_up)
    {
        $this->id = $id;
        $this->name = $name;
        $this->scrambleable = $scrambleable;
        $this->warmable = $warmable;
        $this->signed_up = $signed_up;
        $this->scramble = $scramble;
        $this->no_scramble = $no_scramble;
        $this->warm_up = $warm_up;
    }
}

class RepoEvents extends Repo
{
    protected $months = array(
        'en' => array(
            'January','February','March','April','May','June','July','August','September','October','November','December'
        ),
        'es' => array(
            'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'
        )
    );

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WorldChampsDbConn($this->app));
        }
    }

    public function getAllEvents()
    {
        $this->openConnection();
        return $this->DB->query("SELECT * FROM events ORDER BY rank");
    }

    protected function getSignedUpEvents()
    {
        $this->openConnection();
        $result = $this->DB->query(
            "SELECT event_id FROM registrations " .
            "JOIN events ON events.id=event_id " .
            "WHERE user_id=? AND real_event " .
            "ORDER BY rank",
            array($_SESSION[Application::AUTH_ID])
        );
        return $this->results_to_array($result,"event_id");
    }

    public function getSignedUpEventsIconsStr($user_id)
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");
        $nameCol = "name_".$language;
        $result = $this->DB->query(
            "SELECT event_id, $nameCol FROM registrations " .
            "JOIN events ON events.id=event_id " .
            "WHERE user_id=? AND real_event " .
            "ORDER BY rank",
            array($user_id)
        );
        $str = "";
        foreach ($result as $row) {
            $str .= sprintf("<span class='cubing-icon icon-%s' title='%s'></span>", $row["event_id"], $row[$nameCol]);
        }
        return $str;
    }

    protected function getEventsIconsStr($user_id,$type)
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");
        $nameCol = "name_".$language;
        $result = $this->DB->query(
            "SELECT event_id, $nameCol FROM staff_events " .
            "JOIN events ON events.id=event_id " .
            "WHERE user_id=? AND type=? " .
            "ORDER BY rank",
            array($user_id,$type)
        );
        $str = "";
        foreach ($result as $row) {
            $str .= sprintf("<span class='cubing-icon icon-%s' title='%s'></span>", $row["event_id"], $row[$nameCol]);
        }
        return $str;
    }

    public function getScrambleEventsIconsStr($user_id)
    {
        return $this->getEventsIconsStr($user_id,"s");
    }

    public function getNoScrambleEventsIconsStr($user_id)
    {
        return $this->getEventsIconsStr($user_id,"n");
    }

    public function getWarmUpEventsIconsStr($user_id)
    {
        return $this->getEventsIconsStr($user_id,"w");
    }

    public function getScrambleableEvents()
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");
        $nameCol = "name_".$language;
        return $this->DB->query("SELECT id, $nameCol AS name FROM events WHERE scrambleable ORDER BY rank");
    }

    public function getRealEvents()
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");
        $nameCol = "name_".$language;
        return $this->DB->query("SELECT id, $nameCol AS name FROM events WHERE real_event ORDER BY rank");
    }

    protected function getUserEventsByType($type)
    {
        $this->openConnection();
        $events = $this->DB->query(
            "SELECT event_id FROM staff_events WHERE user_id=? AND type=?",
            array($_SESSION[Application::AUTH_ID],$type)
        );
        return $this->results_to_array($events,"event_id");
    }

    public function getUserEvents()
    {
        $events = $this->getAllEvents();
        $signed_up = $this->getSignedUpEvents();
        $scramble = $this->getUserEventsByType('s');
        $no_scramble = $this->getUserEventsByType('n');
        $warm_up = $this->getUserEventsByType('w');

        $language = $this->app->getVersionValue("language");
        $name = "name_".$language;
        $return = array();
        foreach ($events as $evt) {
            $return[] = new UserEvent(
                $evt["id"],
                $evt[$name],
                ($evt["scrambleable"] == "1"),
                ($evt["warmable"] == "1"),
                (array_search($evt["id"],$signed_up) !== false),
                (array_search($evt["id"],$scramble) !== false),
                (array_search($evt["id"],$no_scramble) !== false),
                (array_search($evt["id"],$warm_up) !== false)
            );
        }
        return $return;
    }

    public function getRegistrationFees(&$frames,&$dateOpening)
    {
        $this->openConnection();

        $language = $this->app->getVersionValue("language");
        $datetimeCol = "date_time_" . ($this->app->isProd() ? "prod" : "dev");
        $frames = $this->DB->query("SELECT $datetimeCol AS datetime FROM registration_frames ORDER BY id");
        $today = date(Repo::DATE_SQL_FORMAT);
        if ($today < $frames[0]["datetime"]) {
            $activePricing = null;
        } else {
            $i = 1;
            while ($i <= 3 && $today > $frames[$i]["datetime"]) {
                $i++;
            }
            $activePricing = chr(64+$i);
        }
        foreach ($frames as &$frame) {
            $date_parse = date_parse_from_format(Repo::DATE_SQL_FORMAT,$frame["datetime"]);
            $month = $this->months[$language][$date_parse["month"]-1];
            if ($language=='en') {
                $frame["date"] = strftime("$month %e",strtotime($frame["datetime"]));
            } else {
                $frame["date"] = strftime("%e de $month",strtotime($frame["datetime"]));
            }
        }
        $dateOpening = $frames[0]["date"];
        $frames = array_slice($frames,1);

        $nameCol = "name_".$language;
        $return = $this->DB->query("SELECT id, $nameCol AS name, priceA, priceB, priceC FROM events ORDER BY rank");
        foreach ($return as &$event) {
            $event["activePricing"] = $activePricing;
        }

        return $return;
    }
}