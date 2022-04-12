<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 8:33 AM
 */
namespace App;

use App\Exception\Exception;
use App\View\Page\PageView;

class Application
{
    const ENV_AUTO = null;
    const ENV_DEV = false;
    const ENV_PROD = true;
    const ENV_DEBUG = true;
    const ENV_NO_DEBUG = false;

    const ROLE_NONE = null;
    const ROLE_USER = 0;
    const ROLE_ADMIN = 1;
    const ROLE_SUPER_ADMIN = 2;

    const AUTH_USER = 'AUTH_USER';
    const AUTH_ID = 'AUTH_ID';
    const AUTH_ROLE = 'AUTH_ROLE';

    const POSTIT_ERROR = 'err';
    const POSTIT_INFO = 'inf';
    const POSTIT_SUCCESS = 'suc';

    const EXPIRED_NEVER = 0;
    const EXPIRED_IN_1_MINUTE = 60;
    const EXPIRED_IN_5_MINUTES = 300;
    const EXPIRED_IN_1_HOUR = 3600;
    const EXPIRED_IN_1_DAY = 86400;
    const EXPIRED_ALWAYS = -1;
    const CACHE_PATH = '../cache/';

    const VERSION_DEPENDENCY_NONE = 0;
    const VERSION_DEPENDENCY_SOFT = 1;
    const VERSION_DEPENDENCY_HARD = 2;

    protected $prod = Application::ENV_AUTO;
    protected $debug = Application::ENV_AUTO;
    protected $webHost = ''; // change it to https:// at your convenience
    protected $webPath = '';
    protected $filesPath = '';
    // override at your convenience
    protected $cookiePrefix = '';
    // Used to encode cookies - Overriding is MANDATORY!
    protected $secret = '';

    protected $paramStr;
    protected $cacheFileName;

    protected $URI;
    protected $versions = array();
    protected $versionDependency = Application::VERSION_DEPENDENCY_NONE;

    protected $startTime;

    public function __construct($prod = Application::ENV_AUTO, $debug = Application::ENV_AUTO)
    {
        $this->startTime = microtime(true);
        session_start();
	include('__private_config__.php');
	$this->cookiePrefix = $CONF_COOKIE_PREFIX;
	$this->secret = $CONF_SECRET;
        $local = $_SERVER['SERVER_NAME']=='localhost' || preg_match($CONF_DEV_REGEX,$_SERVER['SERVER_NAME']);
        if ($prod === Application::ENV_AUTO) {
            $prod = ($local ? Application::ENV_DEV : Application::ENV_PROD);
        }
        if ($debug === Application::ENV_AUTO) {
            $debug = ($local ? Application::ENV_DEBUG : Application::ENV_NO_DEBUG);
        }
        $this->prod = $prod;
        $this->debug = $debug;
        ini_set('display_errors', $debug ? 1 : 0);
        error_reporting($prod ? 0 : E_ALL);
	
	$this->webHost = $this->isProd() ? $CONF_PROD_PROT.$CONF_PROD_HOST : $CONF_DEV_PROT.$CONF_DEV_HOST;
    }

    protected function addVersion($key,$options,$mandatory,$defaulValueCallback=null)
    {
        $this->versions[$key] = array(
            'value' => $options[0],
            'mandatory' => $mandatory,
            'options' => $options,
            'defaultValueCallback' => $defaulValueCallback
        );
    }

    public function getVersionValue($key)
    {
        if (!array_key_exists($key,$this->versions))
            die("'$key' is not a recognized version type");
        /*
        if ($this->versions[$key]['value'] === null)
            die("'$key' version value not available here");
        */
        return $this->versions[$key]['value'];
    }

    protected function getVersionOptions($key)
    {
        if (!array_key_exists($key,$this->versions))
            die("'$key' is not a recognized version type");
        return $this->versions[$key]['options'];
    }

    public function versionToStr($versionDependency=null)
    {
        if (!$versionDependency) $versionDependency = $this->getVersionDependency();
        if ($versionDependency==Application::VERSION_DEPENDENCY_NONE)
            return '';
        $onlyMandatory = ($versionDependency!=Application::VERSION_DEPENDENCY_HARD);
        $versionsToStr = '';
        foreach ($this->versions as $key => $version) {
            if ($onlyMandatory && !$version['mandatory'])
                break;
            $versionsToStr .= '_' . $this->getVersionValue($key);
        }
        return $versionsToStr;
    }

    public function addVersionTerminators(&$terminators)
    {
        foreach ($this->versions as $key => $version) {
            $terminators['version-'.$key] = $this->getVersionValue($key);
        }
    }

    public function addVersionEmbedders($versionKey,&$embedders)
    {
        $index = 0;
        foreach ($this->versions as $key => $version) {
            if ($key == $versionKey)
                break;
            $index++;
        }
        $p = explode('/',$this->URI);
        if (!$p[0]) array_splice($p,0,1);
        foreach ($this->versions[$versionKey]['options'] as $option) {
            $p[$index] = $option;
            $embedders['version-'.$option.'-'.$versionKey] = implode('/',$p);
        }
    }

    public function setVersionDependency($dependency)
    {
        $this->versionDependency = $dependency;
    }

    public function getVersionDependency()
    {
        return $this->versionDependency;
    }

    public function run()
    {
        /*
        echo 'request_uri='.$_SERVER['REQUEST_URI'].'<br>';
        echo 'path_info='.$_SERVER['PATH_INFO'].'<br>';
        echo 'script_name='.$_SERVER['SCRIPT_NAME'].'<br>';
        echo 'host='.$_SERVER['HTTP_HOST'].'<br>';
        */
        //echo $_SERVER['REQUEST_METHOD'].'<br>';
        //$this->webHost .= $_SERVER['HTTP_HOST'];
        //$this->webHost .= 'dev2019.speedcubing.org.au';
        if (array_key_exists('PATH_INFO',$_SERVER)) {
            $this->URI = $_SERVER['PATH_INFO'];
            $this->webPath = $_SERVER['SCRIPT_NAME'];
        } else {
            $this->URI = $_SERVER['REQUEST_URI'];
        }
        if ($this->webPath && ($p = strrpos($this->webPath,'/')) !== false) {
            $this->filesPath = substr($this->webPath,0,$p);
        }
        $pos = strpos($this->URI,'?');
        if ($pos !== FALSE) {
            $this->URI = substr($this->URI, 0, $pos);
        }
        $pos = strpos($_SERVER['REQUEST_URI'],'?');
        if ($pos !== FALSE) {
            $URI_params = substr($_SERVER['REQUEST_URI'], $pos);
        } else {
            $URI_params = '';
        }
        //if ($URI_params) die("URI params = ".$URI_params);
        /*
        echo $this->webHost.$this->webPath.$this->URI.'<br>';
        die();
        */

        $paths = [];
        $u_paths = [];
        $params = [];

        $p = explode('/',$this->URI);
        if (count($p) && !$p[0]) array_splice($p,0,1);

        // versions checking
        if (count($this->versions)) {
            $posUri = 0;
            foreach ($this->versions as &$version) {
                if (!count($p))
                    break;
                if (in_array($p[0],$version['options'])) {
                    $version['value'] = $p[0];
                    $posUri += strlen($p[0]) + 1;
                    array_splice($p,0,1);
                } elseif ($version['mandatory']) {
                    /*
                    $newUri = substr_replace($this->URI,'/'.$version['options'][0],$posUri,0);
                    if ($newUri=='/es/') {
                        $this->redirect($newUri);
                    }
                    print_r($version); echo '<br>--------<br>';
                    die('<'.$newUri.'>');
                    */
                    $defaultValue = false;
                    if ($version['defaultValueCallback']) {
                        $callable = array($this,$version['defaultValueCallback']);
                        if (is_callable($callable))
                            $defaultValue = call_user_func($callable);
                    }
                    if ($defaultValue===false)
                        $defaultValue = $version['options'][0];
                    $newUri = substr_replace($this->URI,'/'.$defaultValue,$posUri,0);
                    //die("redirect run = ".$newUri.$URI_params);
                    $this->redirect($newUri.$URI_params);
                } else {
                    break;
                }
            }
        }

        $inParams = false;
        foreach ($p as $value) {
            if (!$inParams && preg_match('/[^a-z0-9]/',$value)) {
                $inParams = true;
            }
            if ($value) {
                if ($inParams) {
                    $params[] = $value;
                } else {
                    $paths[] = $value;
                    $u_paths[] = strtoupper(substr($value,0,1)).substr($value,1);
                }
            }
        }

        $linked = false;
        $actionFunc = null;
        while (true) {
            $actionFunc = 'action'.implode($u_paths);
            if (is_callable(array($this,$actionFunc),false)) {
                $linked = true;
                break;
            }
            if (count($paths))
            {
                $params = array_merge(array_slice($paths,count($paths)-1,1),$params);
                $paths = array_slice($paths,0,count($paths)-1);
                $u_paths = array_slice($u_paths,0,count($u_paths)-1);
            } else {
                break;
            }
        }
        if (!$linked) {
            die('ERROR');
        }
        $this->paramStr = implode('_',$params);
        try {
            call_user_func_array(array($this,$actionFunc),$params);
        } catch (\Exception $e) {
            if ($this->debug) {
                echo $e->getMessage();
                if (!$this->prod) {
                    echo '<p>';
                    $cs = $e->getTrace();
                    foreach($cs as $c) {
                        if (isset($c['file']) && isset($c['line'])) {
                            echo  $c['file'] . ' - line ' . $c['line'] . '<br />';
                        }
                    }
                }
            }
        }
    }

    private function getUri($base, $path, $full)
    {
        //if ($path && $path[1] != '/') {
            $path = '/'.$path;
        //}
        $path = $base.$path;
        $path = preg_replace('/\/+/','/',$path);
        $path = ($full ? $this->webHost : '').$path;
        return $path;
    }

    public function getWebUri($path = '', $full = false)
    {
        return $this->getUri($this->webPath,$path,$full);
    }

    public function getFileUri($path = '', $full = false)
    {
        return $this->getUri($this->filesPath,$path,$full);
    }

    public function isGET()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'GET');
    }

    public function isPOST($formName=NULL)
    {
        $b = ($_SERVER['REQUEST_METHOD'] == 'POST');
		if ($b && $formName) {
			$b = array_key_exists($formName.'_form',$_POST);
		}
		return $b;
    }

    public function redirect($uri, $status = 302)
    {
        if (!preg_match('/^[http:|https:]/',$uri)) {
            $uri = $this->getWebUri($uri,true);
        }
        header('Location: '.$uri, true, $status);
        die();
    }

    public function isProd()
    {
        return ($this->prod == Application::ENV_PROD);
    }

    public static function addPostIt($type,$msg)
    {
        $_SESSION['postit_'.$type.'_'.uniqid()] = $msg;
    }

    protected function getPostIt(&$type, &$msg)
    {
        foreach ($_SESSION as $key => $value) {
            if (substr($key,0,7) == 'postit_') {
                $type = substr($key,7,3);
                $msg = $value;
                unset($_SESSION[$key]);
                return true;
            }
        }
        return false;
    }

    public function renderPostIt(&$layout)
    {
        // You're practically forced to override this method due to the simple, flat, awful layout
        if ($return = $this->getPostIt($type,$msg)) {
            $layout = sprintf('<div>%s</div>',$msg);
        }
        return $return;
    }

    protected function getLoginPath()
    {
        return 'login';
    }

    protected function getLoggedOutPath()
    {
        return '/';
    }

    public function getLoggedInPath()
    {
        return 'admin';
    }

    public function redirectLogged()
    {
        $this->redirect($this->getLoggedInPath());
    }

    protected function encrypt($text)
    {
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->secret, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    protected function decrypt($text)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->secret, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    protected function setCookie($name,$value,$encrypted=true,$expire=null,$secureInProd=true)
    {
        $name = $this->cookiePrefix . "_" . $name;
        if ($encrypted) $value = $this->encrypt($value);
        if (!$expire) $expire = time()+60*60*24*30; // 30 days by default
        $secure = ($this->isProd() ? $secureInProd : false);
        return setcookie($name,$value,$expire,"/","",$secure,true);
    }

    protected function __existsCookie($convertedName)
    {
        return array_key_exists($convertedName,$_COOKIE);
    }

    protected function existsCookie($name)
    {
        $name = $this->cookiePrefix . "_" . $name;
        return array_key_exists($name,$_COOKIE);
    }

    protected function getCookie($name,$encrypted=true)
    {
        $name = $this->cookiePrefix . "_" . $name;
        if ($this->__existsCookie($name)) {
            $value = $_COOKIE[$name];
            if ($encrypted) $value = $this->decrypt($value);
            return $value;
        } else {
            return null;
        }
    }

    protected function removeCookie($name)
    {
        return $this->setCookie($name,"",false,time()-1);
    }

    public function setPasswordCookie($password)
    {
        setcookie($this->cookiePrefix.'_PASSWORD',$this->encrypt($password),time()+60*60*24*30,"/","",false,true);
    }

    public function authIn($username,$email,$password,$id,$role)
    {
        $thereWereCookies = array_key_exists($this->cookiePrefix.'_EMAIL',$_COOKIE);
        $cookieSet = setcookie($this->cookiePrefix.'_EMAIL',$this->encrypt($email),time()+60*60*24*30,"/","",false,true);
        $this->setPasswordCookie($password);

        $_SESSION[Application::AUTH_USER] = $username;
        $_SESSION[Application::AUTH_ID] = $id;
        $_SESSION[Application::AUTH_ROLE] = $role;
        if (!$thereWereCookies && $cookieSet) {
            $this->addPostIt(
                Application::POSTIT_INFO,
                'This computer will remember you. That means that you won\'t need to log in every time if you enter often. ' .
                'On the other hand, if this is not your computer, you should either use a private session or log out when you\'re done.'
            );
        }
        //$this->redirect($this->getLoggedInPath());
    }

    public function authOut()
    {
        if (array_key_exists(Application::AUTH_ROLE,$_SESSION)) {
            setcookie($this->cookiePrefix.'_EMAIL','',time()-60*60*24*30,"/","",false,true);
            setcookie($this->cookiePrefix.'_PASSWORD','',time()-60*60*24*30,"/","",false,true);
            unset($_COOKIE[$this->cookiePrefix.'_EMAIL']);
            unset($_COOKIE[$this->cookiePrefix.'_PASSWORD']);
            unset($_SESSION[Application::AUTH_USER]);
            unset($_SESSION[Application::AUTH_ID]);
            unset($_SESSION[Application::AUTH_ROLE]);
        }
        $this->addPostIt(Application::POSTIT_INFO,'You logged out successfully');
        $this->redirect($this->getLoggedOutPath());
    }

    public function authBind(&$email,&$password)
    {
        if (array_key_exists($this->cookiePrefix.'_EMAIL',$_COOKIE)) {
            $email = $this->decrypt($_COOKIE[$this->cookiePrefix.'_EMAIL']);
            if (array_key_exists($this->cookiePrefix.'_PASSWORD',$_COOKIE)) {
                $password = $this->decrypt($_COOKIE[$this->cookiePrefix.'_PASSWORD']);
                return ($email && $password);
            }
        }
        return false;
    }

    // use this func if the written URI did not succeed
    public function cancelOriginalCall()
    {
        $this->paramStr = null;
    }

    protected function firewall(
        $minRole, $classViewErrorRole,
        $nArgs,$minNArgs,$maxNArgs, $classViewErrorArgs)
    {
        if ($nArgs < $minNArgs || $nArgs > $maxNArgs) {
            $this->cancelOriginalCall();
            $reflection_class = new \ReflectionClass($classViewErrorArgs);
            new Exception($reflection_class->newInstance($this));
        }
        if ($minRole !== null) {
            if (!array_key_exists(Application::AUTH_ROLE,$_SESSION)) {
                $this->redirect($this->getLoginPath());
            } elseif ($_SESSION[Application::AUTH_ROLE] < $minRole) {
                $reflection_class = new \ReflectionClass($classViewErrorRole);
                new Exception($reflection_class->newInstance($this));
            }
        }
    }

    protected function firewallAndCache(
        $minRole, $classViewErrorRole,
        $nArgs,$minNArgs,$maxNArgs, $classViewErrorArgs,
        $classView)
    {
        $this->firewall($minRole, $classViewErrorRole,$nArgs,$minNArgs,$maxNArgs, $classViewErrorArgs);
        $reflection_class = new \ReflectionClass($classView);
        return $reflection_class->newInstance($this);
    }

    protected function getAuthParam($param)
    {
        if (!array_key_exists($param,$_SESSION)) {
            return null;
        } else {
            return $_SESSION[$param];
        }
    }

    protected function setAuthParam($param,$value)
    {
        if (array_key_exists($param,$_SESSION)) {
            $_SESSION[$param] = $value;
        }
    }

    public function getAuthRole()
    {
        return $this->getAuthParam(Application::AUTH_ROLE);
    }

    public function getAuthId()
    {
        return $this->getAuthParam(Application::AUTH_ID);
    }

    public function getAuthUsername()
    {
        return $this->getAuthParam(Application::AUTH_USER);
    }

    public function setAuthUsername($username)
    {
        return $this->setAuthParam(Application::AUTH_USER,$username);
    }

    protected function getPost($key, $default = null)
    {
        return array_key_exists($key,$_POST) ? $_POST[$key] : $default;
    }

    protected function getCacheFileName($class)
    {
        $paramStr = ($this->paramStr ? '_'.$this->paramStr : '');
        $p = strrpos($class,'\\');
        if ($p) $class = substr($class,$p+1);
        return Application::CACHE_PATH . $class . $paramStr . $this->versionToStr() . '.html';
        //return Application::CACHE_PATH . preg_replace('/\\\\/','_',strtolower($class)). $paramStr . $this->versionToStr() . '.html';
    }

    public function getProductionDatetime($fileName = null)
    {
        $formatDatetime = 'j M, Y H:i:s';
        if ($fileName) {
            return gmdate($formatDatetime,filemtime($this->cacheFileName)) . ' UTC';
        } else {
            return gmdate($formatDatetime) . ' UTC';
        }
    }

    public function controlCacheIn($class,$expiration)
    {
        if ($expiration != Application::EXPIRED_ALWAYS && !$this->isPOST()) {
            $this->cacheFileName = $this->getCacheFileName($class);
            if (file_exists($this->cacheFileName) &&
               ($expiration == Application::EXPIRED_NEVER || time()-filemtime($this->cacheFileName) < $expiration)) {
                $content = file_get_contents($this->cacheFileName);
                PageView::__terminatePage($this,$content,true,$this->getProductionDatetime($this->cacheFileName));
                die($content);
            }
        }
    }

    public function controlCacheOut(&$content,&$datetime)
    {
        $datetime = $this->getProductionDatetime();
        if ($this->cacheFileName) {
            if (!file_exists(Application::CACHE_PATH)) {
                mkdir(Application::CACHE_PATH,0777,true);
            }
            file_put_contents($this->cacheFileName,$content);
            return true;
        }
        return false;
    }

    public function deleteCacheFile($class)
    {
        $p = strrpos($class,'\\');
        if ($p) $class = substr($class,$p+1);
        foreach (glob(Application::CACHE_PATH . $class . '*.html') as $filename) {
            if (file_exists($filename)) unlink($filename);
        }
    }

    public static function deleteCaches()
    {
        $list = scandir(Application::CACHE_PATH);
        $counter = 0;
        foreach ($list as $fileName) {
            if (preg_match('/\.html$/',$fileName)) {
                unlink(Application::CACHE_PATH . $fileName);
                $counter++;
            }
        }
        die("$counter cache files were deleted!");
    }

    public static function agentIsChrome()
    {
        return preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT']);
    }

    public function elapsedTime()
    {
        return microtime(true) - $this->startTime;
    }
}
