<?php

namespace WorldChamps\Repo;

use App\Repo\Repo;
use App\Application;
use WorldChamps\View\Page\NewsPageView;

class RepoNews extends Repo
{
    protected $months = array(
        'en' => array(
            'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'
        ),
        'es' => array(
            'ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'
        )
    );

    public function __construct($app)
    {
        parent::__construct($app);
        $this->addPermissions(Application::ROLE_ADMIN,'date','authorId','title_en','body_en');
    }

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WorldChampsDbConn($this->app));
        }
    }

    public function getNews()
    {
        $this->openConnection();
        $language = $this->app->getVersionValue("language");
        $titleCol = "title_$language";
        $bodyCol = "body_$language";
        $return = $this->DB->query(
            "SELECT news.id, $titleCol AS title, $bodyCol AS body, name, date FROM news JOIN users ON users.id=authorId ORDER BY date DESC"
        );
        foreach ($return as &$news) {
            $date_parse = date_parse_from_format(Repo::DATE_SQL_FORMAT,$news["date"]);
            $month = $this->months[$language][$date_parse["month"]-1];
            if ($language=='en') {
                $news["date"] = strftime("$month %e, %Y",strtotime($news["date"]));
            } else {
                $news["date"] = strftime("%e $month %Y",strtotime($news["date"]));
            }
        }
        return $return;
    }

    public function addNews($values)
    {
        $this->insert('news',$values);
        NewsPageView::deleteCacheFile($this->app);
    }

    public function getNewsById($id)
    {
        $this->openConnection();
        $result = $this->DB->query('SELECT * FROM news WHERE id=?',array($id));
        if (count($result)) {
            return $result[0];
        } else {
            return false;
        }
    }

    public function editNews($id,$values)
    {
        $this->update('news','id',$id,$values);
        NewsPageView::deleteCacheFile($this->app);
    }

    public function deleteNews($id)
    {
        $this->openConnection();
        $this->DB->query('DELETE FROM news WHERE id=?',array($id));
        NewsPageView::deleteCacheFile($this->app);
    }
}