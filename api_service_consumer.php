<?php
/**
 * Because response message is broken into multiple XMLSecEnc parts then a DOMDocument is created for each XMLSecEnc, when
 * loadXML() is called on DOMDocument, the input is invalid xml because it doesn't include necessary namespace which is
 * stored in parent section, that's why warnings are coming up for each section, however it doesn't affect the
 * functionality of the library
 * User: thanhnam.it@gmail.com
 * Date: 4/18/14
 * Time: 2:25 PM
 */

error_reporting(E_ALL ^ E_WARNING);

require_once('api_soap.php');
require_once('api_wsutil.php');
require_once('api_service_exception.php');

// TODO: add log level to library
class ApiServiceConsumer
{
    public $_client;
    protected $_logger;

    public function __construct($config) {
        // read from config
        $addLocalCert = $config['security_config']['add_local_cert'];

        $wsdl = $config['soap_config']['wsdl'];
        $trace = $config['soap_config']['trace_enable'];
        $cacheWSDL = $config['soap_config']['cache_wsdl'];
        $soapVersion = $config['soap_config']['soap_version'];
        $soapCompress = $config['soap_config']['compression'];
        $defaultURI = $config['soap_config']['default_uri'];
        $defaultLocationURI = $config['soap_config']['default_endpoint'];

        $privateKey = $config['keystore_config']['private_key_path'];
        $publicKey = $config['keystore_config']['public_key_path'];
        $sslLocalCertPath = $config['keystore_config']['ssl_local_cert'];
        $certPath = $config['keystore_config']['cert_path'];

        $isEncryptedRequest = $config['security_config']['request_encrypted'];
        $isEncryptedResponse = $config['security_config']['response_encrypted'];

        $logLevel = $config['logging_config']['log_level'];
        $logPath = $config['logging_config']['log_path'];

        // basic options
        $arrayOptions = array(
            "soap_version" => $soapVersion,
            "trace" => $trace,
            "cache_wsdl" => $cacheWSDL,
            "compression" => $soapCompress,
        );

        // local ssl option
        if($addLocalCert === true) {
            if(!empty($sslLocalCertPath) && file_exists($sslLocalCertPath)) {
                $arrayOptions['local_cert'] = $sslLocalCertPath;
            }
            if(!empty($defaultLocationURI)) {
                $arrayOptions['location'] = $defaultLocationURI;
            }
            if(!empty($defaultURI)) {
                $arrayOptions['uri'] = $defaultURI;
            }
        }
        // construct soap client
        $this->_client = new ApiSoap($wsdl, $arrayOptions);

        // set key for request and response security
        $this->_client->setPrivateKey($privateKey);
        $this->_client->setCertificate($certPath);
        $this->_client->setPublicKey($publicKey);
        $this->_client->setEncryptedRequest($isEncryptedRequest);
        $this->_client->setEncryptedResponse($isEncryptedResponse);

        //init logger
        $this->_logger = ApiWSUtil::clientLogger($logPath, $logLevel); //default log will be placed into clientlog/ folder
        $this->_client->setLogger($this->_logger);
    }
    /**
     * @return array of available service methods
     */
    public function getAvailableMethods() {
        return $this->_client->__getFunctions();
    }

    /**
     * Invoke a webservice function with parameters
     * @param $methodName
     * @param $parametersArr
     * @return mixed
     */
    public function invoke($methodName, $parametersArr) {
        try {
            return $this->_client->__soapCall($methodName, $parametersArr);
        }
        catch (Exception $e) {
            $this->handleCommonException($e);
        }
    }

    /**
     * Log soap faults and throw wrapped service exception
     * @param $e
     * @throws ApiServiceException
     * @throws Exception
     */
    public  function handleCommonException($e)
    {
        //wrap and throw exception for further handling
        if ($e instanceof SoapFault) {
            $this->_logger->logError(ApiWSUtil::getLogString($e->getMessage(), $e->faultcode, $e->getTraceAsString()));
            throw new ApiServiceException($e->getMessage(), $e->getCode(), $e);
        } else {
            $this->_logger->logError(ApiWSUtil::getLogString($e->getMessage(), $e->getCode(), $e->getTraceAsString()));
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
?>