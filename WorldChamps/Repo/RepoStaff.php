<?php

namespace WorldChamps\Repo;

use App\Application;
use App\Repo\Repo;
use WorldChamps\WorldChampsApplication;
use WorldChamps\View\Page;
use WorldChamps\View;
use WorldChamps\Email;

class ReturnVoters
{
    public $userVoted;
    public $good_voters_str;
    public $good_votes;
    public $bad_voters_str;
    public $bad_votes;

    public function __construct($userVoted, $good_voters_str, $good_votes, $bad_voters_str, $bad_votes)
    {
        $this->userVoted = $userVoted;
        $this->good_voters_str = $good_voters_str;
        $this->good_votes = $good_votes;
        $this->bad_voters_str = $bad_voters_str;
        $this->bad_votes = $bad_votes;
    }
}

class RepoStaff extends Repo
{
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

    public function apply()
    {
        //var_dump($_POST); die();
        $this->openConnection();
        $_POST["score_taking"] = ($_POST["score_taking"] == "true" ? "1" : "0");
        $_POST["check_in"] = ($_POST["check_in"] == "true" ? "1" : "0");
        $_POST["wca_booth"] = ($_POST["wca_booth"] == "true" ? "1" : "0");
        $_POST["day_18"] = ($_POST["day_18"] == "true" ? "1" : "0");
        $_POST["day_19"] = ($_POST["day_19"] == "true" ? "1" : "0");
        $_POST["day_20"] = ($_POST["day_20"] == "true" ? "1" : "0");
        $_POST["day_21"] = ($_POST["day_21"] == "true" ? "1" : "0");
        $_POST["day_22"] = ($_POST["day_22"] == "true" ? "1" : "0");
        $result = $this->DB->query("SELECT user_id FROM staff WHERE user_id=?",array($_SESSION[Application::AUTH_ID]));
        if (count($result)==1) {
            $this->DB->query(
                "UPDATE staff SET introduction=?, score_taking=?, check_in=?, wca_booth=?, t_shirt_size=?, day_18=?, day_19=?, day_20=?, day_21=?, day_22=? WHERE user_id=?",
                array($_POST["introduction"],$_POST["score_taking"],$_POST["check_in"],$_POST["wca_booth"],$_POST["t_shirt_size"],$_POST["day_18"],$_POST["day_19"],$_POST["day_20"],$_POST["day_21"],$_POST["day_22"],$_SESSION[Application::AUTH_ID])
            );
        } else {
            $this->DB->query(
                "INSERT INTO staff (user_id, introduction, score_taking, check_in, wca_booth, t_shirt_size, day_18, day_19, day_20, day_21, day_22) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array($_SESSION[Application::AUTH_ID],$_POST["introduction"],$_POST["score_taking"],$_POST["check_in"],$_POST["wca_booth"],$_POST["t_shirt_size"],$_POST["day_18"],$_POST["day_19"],$_POST["day_20"],$_POST["day_21"],$_POST["day_22"])
            );
        }
        $this->DB->query("DELETE FROM staff_events WHERE user_id=?",array($_SESSION[Application::AUTH_ID]));
        foreach ($_POST as $key => $post) {
            if (preg_match("/^cb._/",$key)) {
                $this->DB->query(
                    "INSERT INTO staff_events (type, user_id, event_id) VALUES (?, ?, ?)",
                    array(substr($key,2,1),$_SESSION[Application::AUTH_ID],substr($key,4))
                );
            }
        }
    }

    public function getUserStaff($user_id)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT * FROM staff WHERE user_id=?",array($user_id));
        if (count($result)==1) {
            return $result[0];
        } else {
            return null;
        }
    }

    protected function getVotersStr($type,$candidate_id, &$count, &$myVote)
    {
        $this->openConnection();
        $myVote = false;
        $voters = $this->DB->query(
            "SELECT name, voter_id FROM staff_votes " .
            "JOIN users ON users.id=voter_id " .
            "WHERE candidate_id=? AND type=? " .
            "ORDER BY name",
            array($candidate_id,$type)
        );
        $voters_array = array();
        foreach ($voters as $voter) {
            $voters_array[] = $voter["name"];
            if ($voter["voter_id"] == $_SESSION[Application::AUTH_ID]) $myVote = true;
        }
        $count = count($voters_array);
        return implode(", ",$voters_array);
    }

    protected function getStaffByStatus($status,$order)
    {
        $options = array("name","country");
        if ($_SESSION[Application::AUTH_ROLE] == WorldChampsApplication::ROLE_ORGANIZER) {
            $options[] = "votes";
        }
        if (array_search($order,$options)===false) $this->app->error404();
        if ($order=="name" || $order=="votes") {
            $order_st = "users.name";
        } else {
            $order_st = "country, users.name";
        }
        $this->openConnection();
        $return = $this->DB->query(
            "SELECT user_id AS candidate_id, introduction, score_taking, check_in, wca_booth, users.name, users.wca_id, users.email, users.role, Countries.name AS country FROM staff " .
            "LEFT JOIN users ON users.id = user_id " .
            "LEFT JOIN Countries ON country_iso2 = iso2 " .
            "WHERE approved=? " .
            "ORDER BY $order_st",
            array($status)
        );
        $repo_evt = new RepoEvents($this->app);
        foreach($return as &$row) {
            $row["good_voters_str"] = $this->getVotersStr(1,$row["candidate_id"], $row["good_voters_count"], $row["my_good_vote"]);
            $row["bad_voters_str"] = $this->getVotersStr(0,$row["candidate_id"], $row["bad_voters_count"], $row["my_bad_vote"]);
            $row['signed_up_icons'] = $repo_evt->getSignedUpEventsIconsStr($row["candidate_id"]);
            $row['scramble_icons'] = $repo_evt->getScrambleEventsIconsStr($row["candidate_id"]);
            $row['no_scramble_icons'] = $repo_evt->getNoScrambleEventsIconsStr($row["candidate_id"]);
            $row['warm_up_icons'] = $repo_evt->getWarmUpEventsIconsStr($row["candidate_id"]);
            $row['days'] = $this->getDaysLabels($row["candidate_id"]);
        }
        if ($order == "votes") {
            foreach ($return as $key => $row2) {
                $aux[$key] = $row2['good_voters_count']-$row2['bad_voters_count'];
            }
            array_multisort($aux,SORT_DESC,$return);
        }
        return $return;
    }

    public function renderStaff(&$view,$order)
    {
        $approvedStaff = $this->getStaffByStatus(true,$order);
        $candidateStaff = $this->getStaffByStatus(false,$order);
        $view->render(array(
            'staff' => $approvedStaff,
            'staff_count' => count($approvedStaff),
            'candidates' => $candidateStaff,
            'candidates_count' => count($candidateStaff),
            'order' => $order,
            'can_vote' => ($_SESSION[Application::AUTH_ROLE] > 0),
        ));
    }

    public function vote($type,$candidate_id)
    {
        if ($candidate_id == $_SESSION[Application::AUTH_ID]) return null;
        $this->openConnection();
        $result = $this->DB->query(
            "SELECT type FROM staff_votes WHERE voter_id=? AND candidate_id=?",
            array($_SESSION[Application::AUTH_ID],$candidate_id)
        );
        if (count($result)) {
            if ($result[0]["type"] == $type) {
                $this->DB->query(
                    "DELETE FROM staff_votes WHERE voter_id=? AND candidate_id=?",
                    array($_SESSION[Application::AUTH_ID],$candidate_id)
                );
                $userVoted = false;
            } else {
                $this->DB->query(
                    "UPDATE staff_votes SET type=? WHERE voter_id=? AND candidate_id=?",
                    array($type,$_SESSION[Application::AUTH_ID],$candidate_id)
                );
                $userVoted = true;
            }
        } else {
            $this->DB->query(
                "INSERT INTO staff_votes (type, voter_id, candidate_id) VALUES (?, ?, ?)",
                array($type,$_SESSION[Application::AUTH_ID],$candidate_id)
            );
            $userVoted = true;
        }
        $good_voters_str = $this->getVotersStr(1,$candidate_id, $good_voters_count,$dummy);
        $bad_voters_str = $this->getVotersStr(0,$candidate_id, $bad_voters_count, $dummy);
        return json_encode(new ReturnVoters(
                $userVoted,
                $good_voters_str,
                $good_voters_count,
                $bad_voters_str,
                $bad_voters_count
            ));
    }

    public function getDaysLabels($candidate_id)
    {
        $this->openConnection();
        $results = $this->DB->query("SELECT day_18, day_19, day_20, day_21, day_22 from staff WHERE user_id=?",array($candidate_id));
        $return = "";
		
        if (count($results)) {
			$return = "<span class='label label-".($results[0]["day_18"]?"success":"danger")."'>11</span>"; // First morning set-up
            for ($day = 11; $day <= 14; $day++) {
				$hiddenDay = $day + 8; // Euros used day_18 through 22; only front-end has been changed for now
                $return .= "<span class='label label-".($results[0]["day_".$hiddenDay]?"success":"danger")."'>".$day."</span>";
            }
        }
        return $return;
    }

    public function accept($candidate_id)
    {
        $this->openConnection();
        $staff = $this->getUserStaff($candidate_id);
        if (!$staff) die('ERROR - Candidate does not exist');
        if ($staff["approved"]) die('ERROR - Candidate already approved');

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser($candidate_id);
        if (!$user) die('ERROR - User does not exist');

        $repo_pay = new RepoPayments($this->app);

        if ($user["amount_used"]) {
            // update payments
            $charge = new CompensationCharge(CompensationCharge::getToken($this->app->secret),-$user["amount_used"]);
            $created = date(Repo::DATE_SQL_FORMAT,$charge->created);
            $charge->staff = "accept";
            $repo_pay->addPayment(
                $candidate_id,
                $charge->id,
                RepoPayments::COMP,
                $charge->amount,
                $created,
                json_encode($charge)
            );
            // update registrations
            $this->DB->query("UPDATE registrations SET paid_fee=0 WHERE user_id=?",array($candidate_id));
            // update users
            $this->DB->query("UPDATE users SET amount_used=0 WHERE id=?",array($candidate_id));
        }
        // update staff
        $this->DB->query("UPDATE staff SET approved=1 WHERE user_id=?",array($candidate_id));

        // email
        $sender = new Email\Email($this);
        $emailView = new View\Email\AddStaffEmailView($this->app);
        $sender->send(
            true, // $this->app->isProd(),
            $user["email"],
            $sender::wcEmail,
            $sender::wcEmail,
            null,
            "You have been accepted as Staff for WCA World Championship 2019!",
            $emailView->renderView(array(
                    'name' => $user["name"],
                ))
        );

        die('OK');
    }

    public function remove($candidate_id)
    {
        $this->openConnection();
        $staff = $this->getUserStaff($candidate_id);
        if (!$staff) die('ERROR - Candidate does not exist');
        if (!$staff["approved"]) die('ERROR - Candidate is not approved');

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser($candidate_id);
        if (!$user) die('ERROR - User does not exist');

        // update registrations
        $this->DB->query("DELETE FROM registrations WHERE user_id=?",array($candidate_id));
        Page\CompetitorsPageView::deleteCacheFile($this->app);
        Page\PsychsheetPageView::deleteCacheFile($this->app);
        // update staff
        $this->DB->query("UPDATE staff SET approved=0 WHERE user_id=?",array($candidate_id));

        // email
        $sender = new Email\Email($this);
        $emailView = new View\Email\RemoveStaffEmailView($this->app);
        $sender->send(
            true, // $this->app->isProd(),
            $user["email"],
            $sender::wcEmail,
            $sender::wcEmail,
            null,
            "You have been removed as Staff for WCA World Championship 2019",
            $emailView->renderView(array(
                    'name' => $user["name"],
                ))
        );

        die('OK');
    }

    public function erase($candidate_id)
    {
        $this->openConnection();
        $staff = $this->getUserStaff($candidate_id);
        if (!$staff) die('ERROR - Candidate does not exist');
        if ($staff["approved"]) die('ERROR - Candidate is still approved');

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser($candidate_id);
        if (!$user) die('ERROR - User does not exist');

        // get rid of all the staff stuff
        $this->DB->query("DELETE FROM staff_events WHERE user_id=?",array($candidate_id));
        $this->DB->query("DELETE FROM staff_votes WHERE candidate_id=?",array($candidate_id));
        $this->DB->query("DELETE FROM staff WHERE user_id=?",array($candidate_id));

        // email
        $sender = new Email\Email($this);
        $emailView = new View\Email\EraseStaffEmailView($this->app);
        $sender->send(
            true, // $this->app->isProd(),
            $user["email"],
            $sender::wcEmail,
            $sender::wcEmail,
            null,
            "Your staff application for WCA World Championship 2019 has been rejected",
            $emailView->renderView(array(
                    'name' => $user["name"],
                ))
        );

        die('OK');
    }
}
