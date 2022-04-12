<?php

namespace WorldChamps\Lib;

class BasicFuncs
{
    static public function getCountryCodeFromIP($ip=null)
    {
        if (!$ip) $ip = $_SERVER['REMOTE_ADDR'];
        $response = file_get_contents('https://freegeoip.net/json/'.$ip);
        $json = json_decode($response);
        if ($json && property_exists($json,'country_code') && $json->country_code)
            return $json->country_code;
        else
            return null;
    }
}
