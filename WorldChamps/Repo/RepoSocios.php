<?php

namespace Aecr\Repo;

use App\Application;
use App\Repo\Repo;
use Aecr\View;
use Aecr\Email;

class RepoSocios extends Repo
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->addPermissions(Application::ROLE_USER, 'username','dni','fnac','direcc','local','prov','tlf');
        $this->addPermissions(Application::ROLE_ADMIN, 'nombre','caducidad','email','wca');
    }

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new AecrDbConn($this->app));
        }
    }

    public function listSocios($view) {
        $this->openConnection();
        $activos = $this->DB->query('SELECT nombre, wca, DATE_FORMAT(caducidad,"%d-%m-%Y") AS fecha FROM socios WHERE caducidad>=CURDATE() ORDER BY nombre');
        $antiguos = $this->DB->query('SELECT nombre, DATE_FORMAT(caducidad,"%d-%m-%Y") AS fecha FROM socios WHERE caducidad<CURDATE() ORDER BY nombre');
        $view->render(array(
                'activos' => $activos,
                'antiguos' => $antiguos
            ));
    }

    private function strEmails($all=false)
    {
        $this->openConnection();
        $query = "SELECT email FROM socios WHERE email <>''";
        if (!$all) $query .= ' AND caducidad >= CURDATE()';
        $result = $this->DB->query($query);
        $st = '';
        foreach ($result as $r) {
            if ($st) $st .= ',';
            $st .= $r['email'];
        }
        return $st;
    }

    public function notifyComp($id,$name) {
        $view = new View\Email\CompNotificationEmailView($this->app);
        $email = new Email\Email($this->app);
        $addressees = $this->strEmails();
        //$addressees = 'l.ianez@binarema.es,lianez@binarema.es';
        $email->send($this->app->isProd(),null,null,null,$addressees,$name,$view->renderView(
                array(
                    'id' => $id,
                    'name' => $name,
                )
            ));
        $repo = new RepoComps($this->app);
        if ($this->app->isProd()) {
            $repo->setNotified($id);
        }
    }

    public function getSocioById($id)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT * FROM socios WHERE id=?",array($id));
        if (count($result))
            return $result[0];
        else
            return false;
    }

    public function editProfile($id,$values)
    {
        $this->update('socios','id',$id,$values);
        if ($id == $this->app->getAuthId()) {
            $this->app->setAuthUsername($values['username']);
        }
    }

    public function getSociosByRole($role)
    {
        $this->openConnection();
        return $this->DB->query("SELECT * FROM socios WHERE role>=? ORDER BY username",array($role));
    }

    public function getAdminSociosTable()
    {
        $this->openConnection();
        return $this->DB->query('SELECT id, nombre, caducidad FROM socios ORDER BY nombre');
    }

    // these three only admins

    public function addSocio($values)
    {
        $this->insert('socios',$values);
    }

    public function editSocio($id,$values)
    {
        $this->update('socios','id',$id,$values);
        if ($id == $this->app->getAuthId()) {
            $this->app->setAuthUsername($values['username']);
        }
    }

    public function deleteSocio($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM socios WHERE id=?',array($id));
    }
}