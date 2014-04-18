<?php
/**
 * User: thanhnam.it@gmail.com
 * Date: 4/18/14
 * Time: 2:25 PM
 */

require_once('ext/KLogger.php');

class ApiWSUtil
{
    public static function serveWSDL($filename)
    {
        $soap = new DOMDocument('1.0', 'UTF-8');
        if ($soap->load($filename)) {
            header('content-type:text/xml; charset=utf-8');
            echo $soap->saveXML();
            exit;
        } else {
            throw new Exception("WSDL file does not exist");
        }
    }

    public static function serverLogger($folder = 'serverlog', $env = 'PRO')
    {
        $logger = KLogger::instance('serverlog', KLogger::DEBUG, $env);
        return $logger;
    }

    public static function clientLogger($folder = 'clientlog', $env = 'PRO')
    {
        $logger = KLogger::instance('clientlog', KLogger::DEBUG, $env);
        return $logger;
    }

    public static function getLogString($message, $code, $trace)
    {
        return "\n[MESSAGE:" . $message . "]\n[CODE:" . $code . "]\n[TRACE:" . $trace . "]";
    }
}

?>