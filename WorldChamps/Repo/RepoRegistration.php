<?php

namespace WorldChamps\Repo;

use App\Repo\Repo;
use WorldChamps\View\Page;

class RegistrationEvent
{
    public $id;
    public $name;
    public $price_now;
    public $paid_fee;
    public $signed_up;

    public function __construct($id,$name,$price_now,$paid_fee,$signed_up)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price_now = $signed_up ? null : $price_now;
        $this->paid_fee = $paid_fee;
        $this->signed_up = $signed_up;
    }
}

class Registration
{
    public $open;
    public $not_yet_open;
    public $closed;
    public $pricingLetter;
    public $next_change_datetime;
    public $next_change_is_closure;
    public $events;

    public function __construct($open,$not_yet_open,$closed,$pricingLetter,$next_change_datetime,$next_time_is_closure)
    {
        $this->open = $open;
        $this->not_yet_open = $not_yet_open;
        $this->closed = $closed;
        $this->pricingLetter = $pricingLetter;
        $this->next_change_datetime = $next_change_datetime;
        $this->next_change_is_closure = $next_time_is_closure;
    }

    public function addEvent($id,$name,$price_now,$paid_fee,$signed_up)
    {
        $this->events[] = new RegistrationEvent($id,$name,$price_now,$paid_fee,$signed_up);
    }
}

class RepoRegistration extends Repo
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

    protected function getOpeningAndClosure(&$opening,&$closure)
    {
        $this->openConnection();
        $date_time = "date_time_" . ($this->app->isProd() ? "prod" : "dev");
        $results = $this->DB->query("SELECT $date_time FROM registration_frames ORDER BY $date_time");
        $count = count($results);
        if ($count < 2) throw new \Exception("Registration time frames are not set up correctly");
        $opening = $results[0][$date_time];
        $closure = $results[$count-1][$date_time];
    }

    public function checkRegistrationOpen($errorPrefix)
    {
        $this->getOpeningAndClosure($opening,$closure);
        $date = $this->getDateTime();
        if ($date < $opening || $date >= $closure) throw new \Exception($errorPrefix." - registration is closed");
    }

    protected function getRegistrationInitiated()
    {
        $this->openConnection();
        $date_time = "date_time_" . ($this->app->isProd() ? "prod" : "dev");
        $results = $this->DB->query(
            "SELECT id, closure FROM registration_frames WHERE NOW() > $date_time ORDER BY $date_time DESC LIMIT 1"
        );
        if (!count($results)) {
            $not_yet_open = true;
            $closed = true;
            $open = false;
            $pricingLetter = NULL;
        } elseif ($results[0]["closure"]) {
            $not_yet_open = false;
            $closed = true;
            $open = false;
            $pricingLetter = NULL;
        } else {
            $not_yet_open = false;
            $closed = false;
            $open = true;
            $pricingLetter = $results[0]["id"];
        }
        $results = $this->DB->query(
            "SELECT $date_time, closure FROM registration_frames WHERE NOW() < $date_time ORDER BY $date_time LIMIT 1"
        );
        if (!count($results)) {
            $next_change_datetime = NULL;
            $next_change_is_closure = false;
        } else {
            $next_change_datetime = $results[0][$date_time];
            $next_change_is_closure = ($results[0]["closure"] <> 0);
        }
        return new Registration($open,$not_yet_open,$closed,$pricingLetter,$next_change_datetime,$next_change_is_closure);
    }

    public function getRegistrationFromUser($user_id,&$signed_up_to_something)
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");

        $registration = $this->getRegistrationInitiated();

        $repo_staff = new RepoStaff($this->app);
        $staff = $repo_staff->getUserStaff($user_id);
        $isStaff = ($staff && $staff["approved"]);

        $name = "name_".$language;
        $pricing = "price" . $registration->pricingLetter;
        $results = $this->DB->query(
            "SELECT events.id, $name" . ( $registration->open ? ", $pricing" : "" ) . ", paid_fee, registrations.id AS signed_up FROM events " .
            "LEFT JOIN registrations ON user_id=? AND event_id=events.id " .
            "ORDER BY rank",
            array(
                $user_id
            )
        );
        $signed_up_to_something = false;
        foreach ($results as $row) {
            if ($row["signed_up"] !== NULL) $signed_up_to_something = true;
            $registration->addEvent(
                $row["id"],
                $row[$name],
                ($registration->open ? ($isStaff ? "0" : $row[$pricing]) : NULL),
                $row["paid_fee"],
                ($row["signed_up"] === NULL ? FALSE : TRUE )
            );
        }
        return $registration;
    }
}