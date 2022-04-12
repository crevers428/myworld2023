<?php
/**
 * User: luis
 * Date: 6/9/13
 * Time: 2:10 PM
 */
namespace App\Pdo;

class PdoConnection
{
    private $dsn;
    private $user;
    private $pass;

    /* @var \PDO */
    private $DBH;
    private $inTransaction;

    protected $opened;
    protected $lcTimeNames = 'en_US';

    public function __construct($dsn,$user,$pass)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function __destruct()
    {
        $this->commit();
        $this->close();
    }

    protected function open()
    {
        try
        {
            $this->DBH = new \PDO($this->dsn, $this->user, $this->pass);
            $this->opened = true;
        }
        catch (\Exception $e)
        {
            throw new \Exception("\r\n<br /><b>An error occurred trying to open a database connection.".
                "</b><br />Most likely this is a temporary problem - please, try again in a few minutes.");
        }
        $this->query('SET NAMES utf8');
        $this->query('SET lc_time_names='.$this->lcTimeNames);
    }

    function query($query, $array = null)
    {
        if (!$this->opened) {
            $this->open();
        }
        $sth = $this->DBH->prepare($query);
        if (!$sth)
            die("Could not prepare statement<br>\n" .
                "errorCode: " . $this->DBH->errorCode () . "<br>\n" .
                "errorInfo: " . join (", ", $this->DBH->errorInfo ()));
        if ($array === null) {
            $array = array();
        } elseif (!is_array($array)) {
            $array = array($array);
        }
        for ($x=0;$x<count($array);$x++) {
            $sth->bindParam($x+1, $array[$x], (is_int($array[$x]) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
        }
        if (!$sth->execute ())
            die("Could not execute statement<br>\n" .
                "errorCode: " . $sth->errorCode () . "<br>\n" .
                "errorInfo: " . join (", ", $sth->errorInfo ()));
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    function fetch(&$result)
    {
        $row = current($result);
        next($result);
        return $row;
    }

    function insert_id()
    {
        return (int)($this->DBH->lastInsertId());
    }

    public function startTransaction()
    {
        if ($this->inTransaction) {
            throw new \Exception('Transaction in progress - unable to start a new one');
        }
        $this->query('START TRANSACTION');
        $this->inTransaction = true;
    }

    public function commit()
    {
        if ($this->inTransaction) {
            $this->query('COMMIT');
            $this->inTransaction = false;
        }
    }

    function close()
    {
        $DBH = null;
    }

    /*
    function dataReset(&$result)
    {
        reset($result); use this func instead!
    }

    function refererMatchesHost()
    {
        if (!isset($_SERVER['HTTP_REFERER']))
            return false;
        $referer = $_SERVER['HTTP_REFERER'];
        $host = preg_quote($_SERVER['HTTP_HOST']);
        return preg_match("+^(http://)?$host+", $referer);
    }
    */
}