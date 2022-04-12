<?php

namespace App\Repo;

use App\Auth;
use App\Exception\Exception;
use App\Application;

abstract class RepoAuth extends Repo
{
    protected $max_login_attempts = 5;
    protected $signUpTableName = 'signup';
    protected $usersTableName = 'users';
    protected $classCodeDoesNotExistView = null;       // mandatory to override!
    protected $classExceptionDoesNotExistView = null;  // mandatory to override!

    abstract protected function openConnection();

    /*
    protected function sendActivationEmail($username,$email,$activation)
    {
        $view = new ActivationEmailView($this->app);
        Email::send(
            $this->app->isProd(),
            $email,
            null,
            'account activation',
            $view->renderView(array(
                    'username' => $username,
                    'code' => $activation
                ))
        );
        $view = new Page\ActivationSentPageView($this->app);
        $view->render(array('email' => $email));
    }
    */

    public function signupInsert($username,$email,$password)
    {
        $this->openConnection();
        $date = Repo::getDateTime('P1D');
        $activation_code = substr(md5(uniqid(null,true).rand(1000,9999)),0,20);
        $hash = Auth\PasswordHash::create_hash($password);
        $hash = explode(':',$hash);
        $salt = $hash[Auth\PasswordHash::HASH_SALT_INDEX];
        $password = $hash[Auth\PasswordHash::HASH_PBKDF2_INDEX];
        $this->DB->startTransaction();
        $this->DB->query(
            "INSERT INTO {$this->signUpTableName} SET username=?, email=?, salt=?, password=?, activation_code=?, expiration=?",
            array(
                $username,
                $email,
                $salt,
                $password,
                $activation_code,
                $date
            )
        );
        $this->DB->commit();
        //
        //$this->sendActivationEmail($username,$email,$activation_code);
        //
        return $activation_code;
    }

    public function updatePassword($id,$password)
    {
        $hash = Auth\PasswordHash::create_hash($password);
        $hash = explode(':',$hash);
        $salt = $hash[Auth\PasswordHash::HASH_SALT_INDEX];
        $password = $hash[Auth\PasswordHash::HASH_PBKDF2_INDEX];
        $this->openConnection();
        $this->DB->startTransaction();
        $this->DB->query(
            "UPDATE {$this->usersTableName} SET salt=?, password=? WHERE id=?",
            array(
                $salt,
                $password,
                $id
            )
        );
        $this->DB->commit();
        //$this->app->setPasswordCookie($password);
    }

    protected function signupDeleteExpired()
    {
        $this->DB->query("DELETE FROM {$this->signUpTableName} WHERE expiration < NOW()");
    }

    protected function errorCodeDoesNotExist()
    {
        $reflection_class = new \ReflectionClass($this->classCodeDoesNotExistView);
        new Exception($reflection_class->newInstance($this->app));
    }

    // See comments at signupInsert()
    public function signupActivate($code)
    {
        $this->openConnection();
        $this->signupDeleteExpired();
        $result = $this->DB->query("SELECT * FROM {$this->signUpTableName} WHERE activation_code = ?", $code);
        if (!count($result)) $this->errorCodeDoesNotExist();
        $this->DB->startTransaction();
        $this->DB->query("DELETE FROM {$this->signUpTableName} WHERE id = ?", $result[0]['id']);
        $existingEmail = $this->DB->query("SELECT id FROM {$this->usersTableName} WHERE email = ?",array($result[0]['email']));
        $existingUsername = $this->DB->query("SELECT id FROM {$this->usersTableName} WHERE username = ?",array($result[0]['username']));
        $cEmail = count($existingEmail)==1;
        $cUsername = count($existingUsername)==1;
        if (($cEmail xor $cUsername) || ($cEmail && $cUsername && $existingEmail[0]['id']!=$existingUsername[0]['id'])) {
            $this->app->addPostIt(Application::POSTIT_ERROR,'O el correo electrónico o el nombre de usuario están en uso');
            $this->errorCodeDoesNotExist();
        }
        if ($cEmail) {
            $this->DB->query(
                "UPDATE {$this->usersTableName} SET username=?, email=?, role=0, salt=?, password=?, failed_login_attempts=0, activation_time=NOW() WHERE id=?",
                array(
                    $result[0]['username'],
                    $result[0]['email'],
                    $result[0]['salt'],
                    $result[0]['password'],
                    $existingEmail[0]['id']
                )
            );
        } else {
            $this->DB->query(
                "INSERT INTO {$this->usersTableName} SET username=?, email=?, role=0, salt=?, password=?, failed_login_attempts=0, creation_time=NOW()",
                array(
                    $result[0]['username'],
                    $result[0]['email'],
                    $result[0]['salt'],
                    $result[0]['password']
                )
            );
        }
        $this->DB->commit();
        $this->app->addPostIt(Application::POSTIT_SUCCESS,'Your account has just been activated!');
        $this->app->redirectLogged();
    }

    public function availableUsername($username)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT id FROM {$this->usersTableName} WHERE username = ?", $username);
        if (count($result)) {
            return false;
        }
        $this->signupDeleteExpired();
        $result = $this->DB->query("SELECT id FROM {$this->signUpTableName} WHERE username = ?", $username);
        if (count($result)) {
            return false;
        }
        return true;
    }

    public function availableEmail($email)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT id FROM {$this->usersTableName} WHERE email = ?", $email);
        if (count($result)) {
            return false;
        }
        $this->signupDeleteExpired();
        $result = $this->DB->query("SELECT id FROM {$this->signUpTableName} WHERE email = ?", $email);
        if (count($result)) {
            return false;
        }
        return true;
    }

    /*
    private function getDiffStr(&$orig, $q,$str)
    {
        if ($q) {
            if ($orig) {
                $orig = str_replace(' and ',', ',$orig) . ' and ';
            }
            $orig .= $q.' '.$str.($q==1 ? '' : 's');
        }
    }

    private function banLeftStr(\DateInterval $diff)
    {
        if (!$diff->y && !$diff->m && !$diff->d && !$diff->h && !$diff->i) {
            return 'Less than one minute';
        } else {
            $left = '';
            $this->getDiffStr($left,$diff->y,'year');
            $this->getDiffStr($left,$diff->m,'month');
            $this->getDiffStr($left,$diff->d,'day');
            $this->getDiffStr($left,$diff->h,'hour');
            $this->getDiffStr($left,$diff->i,'minute');
            return $left;
        }
    }
    */

    public function authenticate($email,$password)
    {
        $return = true;
        $this->openConnection();
        $result = $this->DB->query(
            "SELECT id, username, role, email, salt, password, failed_login_attempts FROM {$this->usersTableName} WHERE email = ? AND failed_login_attempts < ?",
            array (
                $email,
                $this->max_login_attempts
            )
        );
        if (!count($result)) { // email not found
            $return = false;
        } else {
            $good_hash =
                Auth\PasswordHash::PBKDF2_HASH_ALGORITHM.':'.
                Auth\PasswordHash::PBKDF2_ITERATIONS.':'.
                $result[0]['salt'].':'.
                $result[0]['password'];
            if (!Auth\PasswordHash::validate_password($password,$good_hash)) { // bad password
                $this->DB->query(
                    "UPDATE {$this->usersTableName} SET failed_login_attempts = failed_login_attempts+1 WHERE id=?",
                    $result[0]['id']
                );
                $return = false;
            } else {
                /*
                // banned?
                $now = new \DateTime();
                $banned = new \DateTime($result[0]['ban_date']);
                if ($banned > $now) { // banned!
                    $left = $this->banLeftStr ($banned->diff($now));
                    $view = new Page\BannedView($this->app);
                    $view->render(array(
                            'reason' => $result[0]['ban_reason'],
                            'left' => $left,
                        )
                    );
                }
                */
                // everything is fine
                if ($result[0]['failed_login_attempts']) {
                    $this->DB->query(
                        "UPDATE {$this->usersTableName} SET failed_login_attempts = 0 WHERE id=?",
                        $result[0]['id']
                    );
                }
                $this->app->authIn(
                    $result[0]['username'],
                    $result[0]['email'],
                    $password,
                    $result[0]['id'],
                    $result[0]['role']
                );
            }
        }
        if (!$return) {
            $this->app->addPostIt(
                Application::POSTIT_ERROR,
                //'Combinación incorrecta de correo-e / clave - <a href="'.$this->app->getWebUri('activacion').'">Did you forget?</a>'
                'Combinación incorrecta de correo-e / clave'
            );
        }
        return $return;
    }

    public function activation($email)
    {
        $this->openConnection();
        // first check signs up
        $this->signupDeleteExpired();
        $result = $this->DB->query("SELECT username, activation_code FROM {$this->signUpTableName} WHERE email = ?", $email);
        if (count($result)) {
            $this->sendActivationEmail($result[0]['username'],$email,$result[0]['activation_code']);
        } else {
            // then check users
            $result = $this->DB->query("SELECT username FROM {$this->usersTableName} WHERE email = ?", $email);
            if (count($result)) {
                $this->signupInsert($result[0]['username'], $email, null);
            } else {
                // should never reach here
                $reflection_class = new \ReflectionClass($this->classExceptionDoesNotExistView);
                new Exception($reflection_class->newInstance($this->app));
            }
        }
    }

}