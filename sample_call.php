<?php
/**
 * User: thanhnam.it@gmail.com
 * Date: 4/18/14
 * Time: 2:25 PM
 */
require_once('api_service_locator.php');

try {
    $serviceLocator = new ApiServiceLocator();
    $serviceLocator->locateService('demo_secured_java_ws');
    $data = array();
    $result = $serviceLocator->invoke('getStock',$data);
}
catch (ApiServiceException $ase) {
    echo "Error >> ".$ase->getSoapFault()." >> Details: ".$ase->getMessage();
}
catch (Exception $ex) {
    echo "Error details: ".$ex->getMessage();
}