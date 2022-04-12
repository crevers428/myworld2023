<?php

namespace WorldChamps\Repo;

use App\Application;
use App\Repo\Repo;
use WorldChamps\View\Page;

class RepoUsers extends Repo
{
    const ORDER_NAME = 0;
    const ORDER_COUNTRY = 1;

    const FORMAT_SINGLE = 0;
    const FORMAT_MEAN = 1;
    const FORMAT_AVERAGE = 2;

    const RESULT_TIME = 0;
    const RESULT_MOVES = 1;
    const RESULT_MULTI = 2;

    const TOP_THRESHOLD = 25;

    protected $months = array(
        'en' => array(
            'January','February','March','April','May','June','July','August','September','October','November','December'
        ),
        'es' => array(
            'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'
        )
    );

    public function __construct($app)
    {
        parent::__construct($app);
        /*
        $this->addPermissions(Application::ROLE_USER, 'username','dni','fnac','direcc','local','prov','tlf');
        $this->addPermissions(Application::ROLE_ADMIN, 'nombre','caducidad','email','wca');
        */
    }

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WorldChampsDbConn($this->app));
        }
    }

    public function renderMyWorlds()
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");

        $results = $this->DB->query(
            "SELECT users.*, Countries.name as country FROM users " .
            "LEFT JOIN Countries ON Countries.iso2 = users.country_iso2 WHERE users.id=?",
            array($_SESSION[Application::AUTH_ID]));
        if (count($results) <> 1) {
            throw new \Exception('User ID not found!');
        }
        $results = $results[0];

        $repo_registration = new RepoRegistration($this->app);
        $registration = $repo_registration->getRegistrationFromUser($_SESSION[Application::AUTH_ID],$signed_up_to_something);
        if (!$registration->next_change_datetime) {
            $date_str = ""; // unused
        } else {
            $date_parse = date_parse_from_format(Repo::DATE_SQL_FORMAT,$registration->next_change_datetime);
            $month = $this->months[$language][$date_parse["month"]-1];
            if ($language=='en') {
                $date_str = strftime("$month %e, %Y at %l:%M %P",strtotime($registration->next_change_datetime));
            } else {
                $date_str = strftime("%e de $month de %Y a las %l:%M %P",strtotime($registration->next_change_datetime));
            }
        }

        $repo_pay = new RepoPayments($this->app);

        $repo_staff = new RepoStaff($this->app);
        $staff = $repo_staff->getUserStaff($_SESSION[Application::AUTH_ID]);

        $repo_evt = new RepoEvents($this->app);
        $user_events = $repo_evt->getUserEvents();

        $repo_tickets = new RepoTickets($this->app);
        $tickets = $repo_tickets->getTicketsByUser($_SESSION[Application::AUTH_ID],$has_tickets);

        $view = new Page\MyWorldsPageView($this->app);
        $view->render(array(
                'name' => $results['name'],
                'email' => $results['email'],
                'country' => $results['country'],
                'delegate' => ($results["delegate_status"]?true:false),
                'avatar' => $results['avatar_thumb_url'],
                'wca_id' => $results['wca_id'],
                'amount_paid' => $results['amount_paid'],
                'amount_used' => $results["amount_used"],
                'balance' => max(0,$results["amount_paid"] - $results["amount_used"]),
                'signed_up_to_something' => $signed_up_to_something,

                'open' => $registration->open,
                'not_yet_open' => $registration->not_yet_open,
                'closed' => $registration->closed,
                'next_change_datetime' => $date_str,
                'next_change_is_closure' => $registration->next_change_is_closure,
                'registration' => json_encode($registration),
                'commission' => RepoPayments::commission,

                'payments' => $repo_pay->getPayments($paid_totals,$comm_totals),
                'paid_totals' => $paid_totals,
                'comm_totals' => $comm_totals,

                'staff_applied' => ($staff?true:false),
                'staff_approved' => ($staff?$staff["approved"]:false),
                'staff_introduction' => ($staff?$staff["introduction"]:""),

                'user_events' => json_encode($user_events),
                'score_taking' => ($staff?$staff["score_taking"]:null),
                'check_in' => ($staff?$staff["check_in"]:null),
                'wca_booth' => ($staff?$staff["wca_booth"]:null),
                't_shirt_size' => $staff["t_shirt_size"],
                'day_18' => ($staff?$staff["day_18"]:null),
                'day_19' => ($staff?$staff["day_19"]:null),
                'day_20' => ($staff?$staff["day_20"]:null),
                'day_21' => ($staff?$staff["day_21"]:null),
                'day_22' => ($staff?$staff["day_22"]:null),

                'has_tickets' => $has_tickets,
                'max_companions' => RepoTickets::MAX_COMPANION_TICKETS_PER_COMPETITOR,
                'tickets' => json_encode($tickets),
                'one_day_fees' => RepoTickets::ONE_DAY_FEES,
                'all_days_fees' => RepoTickets::ALL_DAYS_FEES,

                'user_id' => $_SESSION[Application::AUTH_ID],
            ));
    }

    /* Sample
    object(stdClass)#4 (1) {
      ["me"]=>
      object(stdClass)#5 (16) {
        ["class"]=>
        string(4) "user"
        ["url"]=>
        string(55) "https://www.worldcubeassociation.org/persons/2009PARE02"
        ["id"]=>
        int(17)
        ["wca_id"]=>
        string(10) "2009PARE02"
        ["name"]=>
        string(15) "Luis J. IÃ¡Ã±ez"
        ["gender"]=>
        string(1) "m"
        ["country_iso2"]=>
        string(2) "ES"
        ["delegate_status"]=>
        string(12) "board_member"
        ["created_at"]=>
        string(24) "2015-03-18T20:07:03.000Z"
        ["updated_at"]=>
        string(24) "2017-12-03T18:28:37.000Z"
        ["teams"]=>
        array(1) {
          [0]=>
          object(stdClass)#6 (2) {
            ["friendly_id"]=>
            string(3) "wrt"
            ["leader"]=>
            bool(false)
          }
        }
        ["avatar"]=>
        object(stdClass)#7 (3) {
          ["url"]=>
          string(82) "https://www.worldcubeassociation.org/uploads/user/avatar/2009PARE02/1469052143.jpg"
          ["thumb_url"]=>
          string(88) "https://www.worldcubeassociation.org/uploads/user/avatar/2009PARE02/1469052143_thumb.jpg"
          ["is_default"]=>
          bool(false)
        }
        ["dob"]=>
        string(10) "1968-09-27"
        ["email"]=>
        string(20) "luisjianez@gmail.com"
        ["region"]=>
        string(13) "World (Spain)"
        ["senior_delegate_id"]=>
        NULL
      }
    }
    */

    public function getRole($user)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT * FROM users WHERE id=?",array($user->me->id));
        if (count($result)) {
            if ($user->me->delegate_status && !$result[0]["role"]) {
                $role = 1;
            } elseif (!$user->me->delegate_status && ($result[0]["role"] == 1)) {
                $role = 0;
            } else {
                $role = $result[0]["role"];
            }
            $this->DB->query(
                'UPDATE users ' .
                'SET wca_id=?, name=?, gender=?, country_iso2=?, delegate_status=?, avatar_thumb_url=?, dob=?, email=?, role=? ' .
                'WHERE id=?',
                array(
                    $user->me->wca_id,
                    $user->me->name,
                    $user->me->gender,
                    $user->me->country_iso2,
                    $user->me->delegate_status,
                    $user->me->avatar->thumb_url,
                    $user->me->dob,
                    $user->me->email,
                    $role,
                    $user->me->id,
                )
            );
        } else {
            $role = ($user->me->delegate_status ? 1 : 0);
            $this->DB->query(
                'INSERT INTO users (id, wca_id, name, gender, country_iso2, delegate_status, avatar_thumb_url, dob, email, role) ' .
                'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    $user->me->id,
                    $user->me->wca_id,
                    $user->me->name,
                    $user->me->gender,
                    $user->me->country_iso2,
                    $user->me->delegate_status,
                    $user->me->avatar->thumb_url,
                    $user->me->dob,
                    $user->me->email,
                    $role,
                )
            );
        }
        return $role;
    }

    public function getUser($user_id)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT * FROM users WHERE id=?",$user_id);
        if (count($result)==1) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function getUsersByRole($role)
    {
        $this->openConnection();
        return $this->DB->query("SELECT * FROM users WHERE role=? ORDER BY name",$role);
    }

    public function getCompetitors($order_code, &$totals)
    {
        if ($order_code == RepoUsers::ORDER_NAME) {
            $order = "name";
        } elseif ($order_code == RepoUsers::ORDER_COUNTRY) {
            $order = "country, name";
        } else {
            throw new \Exception("getCompetitors - order not supported");
        }
        $this->openConnection();
        $results = $this->DB->query(
            "SELECT users.id, users.name, wca_id, Countries.name AS country, IF(avatar_thumb_url LIKE '%missing%',null,avatar_thumb_url) AS avatar_url, yt_video_id " .
            "FROM users " .
            "LEFT JOIN Countries ON iso2=country_iso2 " .
            "WHERE users.id IN (SELECT DISTINCT user_id FROM registrations) " .
            "ORDER BY $order"
        );
        $language = $this->app->getVersionValue("language");
        $name = "name_$language";
        $and = ($language=='en'?' and ':' y ');
        $totals = array(20);
        for ($i=0;$i<20;$i++) $totals[$i] = array('count' => 0);
        $totals[0]["count"] = count($results);
        $existingCountries = array();
        foreach ($results as &$row) {
            $row["events"] = $this->DB->query(
                "SELECT events.id, $name AS name, registrations.id AS signed_up " .
                "FROM events " .
                "LEFT JOIN registrations ON user_id=? AND event_id=events.id " .
                "WHERE real_event " .
                "ORDER BY rank",
                array(
                    $row["id"]
                )
            );
            if (!array_key_exists($row["country"],$existingCountries)) {
                $existingCountries[$row["country"]] = true;
                $totals[1]["count"]++;
            }
            $i = 2;
            foreach ($row["events"] as $evt) {
                if ($evt["signed_up"]) $totals[$i]["count"]++;
                $i++;
            }

            if (!$row["wca_id"]) {
                $options = array();
            } else {
                $options = $this->DB->query(
                    "SELECT $name AS name FROM events " .
                    "WHERE " .
                        "id IN (SELECT event_id FROM registrations WHERE user_id=?) AND (" .
                            "(competition_format = 0 AND id IN (SELECT eventId FROM RanksSingle WHERE personId=? AND worldRank <= ?)) " .
                        "OR " .
                            "(competition_format > 0 AND id IN (SELECT eventId FROM RanksAverage WHERE personId=? AND worldRank <= ?))" .
                    ") ORDER BY rank",
                    array($row["id"],$row["wca_id"],RepoUsers::TOP_THRESHOLD,$row["wca_id"],RepoUsers::TOP_THRESHOLD)
                );
            }
            $row["options"] = "";
            for ($j = 0; $j < count($options); $j++) {
                if ($j == 0) {
                    $row["options"] = $options[$j]["name"];
                } elseif ($j == count($options)-1) {
                    $row["options"] .= $and.$options[$j]["name"];
                } else {
                    $row["options"] .= ', '.$options[$j]["name"];
                }
            }
        }
        $totals[0]["count"] .= ($language == "en" ? " people" : " personas");
        $totals[1]["count"] .= ($language == "en" ? " countries" : " países");
        return $results;
    }

    public static function wcaResultToString($result,$resultType)
    {
        if (!$result) {
            return ' '; // needs a space
        } else if ($result == -1) {
            return 'DNF';
        } else if ($result == -2) {
            return 'DNS';
        } else {
            switch ($resultType) {
                case RepoUsers::RESULT_TIME:
                    $hh = $result % 100;
                    $hhStr = intval($hh,10);
                    if (strlen($hhStr) < 2) $hhStr = '0' . $hhStr;
                    $result = floor($result / 100);
                    $ss = $result % 60;
                    $mm = floor($result / 60);
                    if ($mm) {
                        $ssStr = intval($ss,10);
                        if (strlen($ssStr) < 2) $ssStr = '0' . $ssStr;
                        return $mm.':'.$ssStr.'.'.$hhStr;
                    } else {
                        return $ss.'.'.$hhStr;
                    }
                case RepoUsers::RESULT_MOVES:
                    if (strlen($result) == 4) {
                        $result = substr($result,0,2) . '.' . substr($result,2,2);
                    }
                    return $result;
                case RepoUsers::RESULT_MULTI:
                    $MM = $result % 100;
                    $result = floor($result / 100);
                    $TTTTT = $result % 100000;
                    $result = floor($result / 100000);
                    $DD = $result;
                    $points = 99 - $DD;
                    $attempted = $points + $MM * 2;
                    $solved = $attempted - $MM;
                    $ss = $TTTTT % 60;
                    $ssStr = intval($ss,10);
                    if (strlen($ssStr) < 2) $ssStr = '0' . $ssStr;
                    $mm = floor($TTTTT / 60);
                    return $solved . '/' . $attempted . ' ' . $mm . ':' . $ssStr;
                default:
                    throw new \Exception('Unsupported results type!');
            }
        }
    }

    public function getPsychsheet($eventId, &$name1, &$name2)
    {
        $this->openConnection();
        $event = $this->DB->query("SELECT * FROM events WHERE id=?",array($eventId));
        $event = $event[0];
        $bySingle = ($event["competition_format"] == RepoUsers::FORMAT_SINGLE);
        $language = $this->app->getVersionValue("language");

        $vars = array($eventId,$eventId);
        $query = "SELECT wca_id, users.name, Countries.name AS country, ";
        if (!$event["has_average"]) {
            $query .= "RanksSingle.best AS t1, RanksSingle.worldRank AS r1, NULL AS t2 ";
            $name1 = "Single";
            $name2 = null;
        } elseif ($bySingle) {
            $query .= "RanksAverage.best AS t2, RanksSingle.best AS t1, RanksAverage.worldRank AS r2, RanksSingle.worldRank AS r1 ";
            $name1 = "Single";
            $name2 = ($language == "en" ? "Mean" : "Media"); // only 333bf
        } else {
            $query .= "RanksAverage.best AS t1, RanksSingle.best AS t2, RanksAverage.worldRank AS r1, RanksSingle.worldRank AS r2 ";
            $name1 = ($language == "en" ?
                        ($event["competition_format"] == RepoUsers::FORMAT_MEAN ? "Mean" : "Average") :
                        "Media"
                     );
            $name2 = "Single";
        }
        $query .= "FROM users ".
            "LEFT OUTER JOIN RanksSingle ON RanksSingle.personId = users.wca_id AND RanksSingle.eventId = ? ";
        if ($event["has_average"]) {
            $query .= "LEFT OUTER JOIN RanksAverage ON RanksAverage.personId = users.wca_id AND RanksAverage.eventId = ? ";
            $vars[] = $eventId;
        }
        $query .= "LEFT JOIN Countries ON Countries.iso2 = country_iso2 " .
            "WHERE users.id IN (SELECT DISTINCT user_id FROM registrations WHERE event_id=?) ";
        $query .= $bySingle ?
            "ORDER BY r1 IS NULL, r1, name"
            :
            "ORDER BY r1 IS NULL, r1, r2 IS NULL, r2, name";
        $competitors = $this->DB->query(
            $query,
            $vars
        );

        //var_dump($competitors);

        $last_r1 = 0;
        $last_r2 = 0;
        $counter = 1;
        foreach ($competitors as &$competitor) {
            $competitor["t1"] = $this::wcaResultToString($competitor["t1"],$event["result_type"]);
            if ($name2) {
                $competitor["t2"] = $this::wcaResultToString($competitor["t2"],$event["result_type"]);
                $competitor["position"] = (
                    $competitor["r1"] > $last_r1 || $competitor["r2"] > $last_r2 ? $counter : ""
                );
            } else {
                $competitor["position"] = (
                    $competitor["r1"] > $last_r1 ? $counter : ""
                );
            }
            $counter++;
            $last_r1 = $competitor["r1"];
            if ($name2) $last_r2 = $competitor["r2"];
        }
        //echo "<br><br><br>";
        //var_dump($competitors); die();
        return $competitors;
    }
}