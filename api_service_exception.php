<?php
/**
 * User: thanhnam.it@gmail.com
 * Date: 4/18/14
 * Time: 2:25 PM
 */

class ApiServiceException extends Exception
{
    private $_soapFault;

    public $serviceFaultCode;
    public $serviceFaultString;
    public $serviceExceptionType; //exception type based on wsdl
    public $serviceTrace;


    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if (isset($previous) && $previous instanceof SoapFault) {
            $this->_soapFault = $previous;
            $this->serviceFaultCode = $previous->faultcode;
            $this->serviceFaultString = $previous->faultstring;
            if (isset($previous->detail)) {
                $stdObj = $previous->detail;
                foreach ($stdObj as $prop_name => $prop_val) {
                    if ($stdObj->$prop_name instanceof SoapVar) {
                        $this->serviceExceptionType = $stdObj->$prop_name->enc_stype;
                        break;
                    }
                }
            }
            $this->serviceTrace = $previous->getTraceAsString();
        }
    }

    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    //return previous exception
    public function getSoapFault()
    {
        return $this->_soapFault;
    }

}