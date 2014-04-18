<?php
/**
 * User: thanhnam.it@gmail.com
 * Date: 24/12/13
 * Time: 3:00 PM
 */

require_once('api_service_consumer.php');

// TODO: cache service client ?

class ApiServiceLocator {

    static $serviceInstances = array(); // to store all in-use service instances
    static $serviceConfigurations = array();
    /**
     * Locate the service based on config file and construct secure soap client
     * @param $serviceName
     * @return ApiServiceConsumer
     * @throws ApiServiceException
     */
    public static function locateService($serviceName) {

        // load configuration from cache
        $serviceConfigurations = self::getServiceConfiguration();

        // check if service name is available
        if(!array_key_exists($serviceName, $serviceConfigurations)) {
            throw new ApiServiceException("Service $serviceName doesn't exist");
        }

        // check if instance was initiated
        if(!self::isServiceInstanceExist($serviceName)) {
            try {
                $service = new ApiServiceConsumer($serviceConfigurations[$serviceName]);
                self::addServiceInstanceToCache($serviceName, $service);
                return $service;
            }
            catch(Exception $ex) {
                // throw exception if failed to initiate the service
                throw new ApiServiceException("Failed to initiate the service $serviceName: ".$ex->getMessage(), 0, $ex);
            }
        }
        else {
            return self::getExistingServiceInstance($serviceName);
        }
    }

    public static function getServiceConfiguration() {
        if(!empty(self::$serviceConfigurations)) return self::$serviceConfigurations;
        else {
            // get from default location
            $configurations = @json_decode(file_get_contents('wsconfig.config'), true);
            if($configurations === null && json_last_error() !== JSON_ERROR_NONE) throw new Exception('Can not decode json configuration file');
            if(!empty($configurations['config_location'])) {
                $configurations = @json_decode(file_get_contents($configurations['config_location']), true);
                if($configurations === null && json_last_error() !== JSON_ERROR_NONE) throw new Exception('Can not decode json configuration file');
            }
            self::$serviceConfigurations = $configurations;
            return $configurations;
        }
    }

    // TODO: to clear service cache
    public static function clearServiceCache() {
        self::$serviceInstances = array();
    }

    public static function addServiceInstanceToCache($serviceName, $service) {
        self::$serviceInstances[$serviceName] = $service;
    }

    public static function isServiceInstanceExist($serviceName) {
        return array_key_exists($serviceName, self::$serviceInstances);
    }

    public static function getAllServiceInstances() {
        return self::$serviceInstances;
    }

    public static function getExistingServiceInstance($serviceName) {
        return self::$serviceInstances[$serviceName];
    }

}