<?php
/**
 * Default using signature to sign the message
 * Enable encryption if necessary
 * Support local cert to communicate
 * User: thanhnam.it@gmail.com
 * Date: 4/18/14
 * Time: 2:25 PM
 */
require_once('ext/soap-wsse.php');

class ApiSoap extends SoapClient
{

    private $_privateKey;
    private $_cert;
    private $_publicKey;
    private $_isEncrypted;
    private $_isEncryptedRequest;
    private $_isEncryptedResponse;

    public function __construct($wsdl, $arrayOptions)
    {
        parent::__construct($wsdl, $arrayOptions);
    }

    public function setPrivateKey($keyname)
    {
        $this->_privateKey = $keyname;
    }

    public function setCertificate($certname)
    {
        $this->_cert = $certname;
    }

    public function setPublicKey($keyname)
    {
        $this->_publicKey = $keyname;
    }

    //set encryption both way
    public function setEncrypted($encrypted)
    {
        if ($encrypted == TRUE && !isset($this->_publicKey)) {
            echo "Must provide service key for encryption";
            exit;
        } else {
            $this->_isEncrypted = $encrypted;
        }
    }

    public function setEncryptedRequest($encrypted)
    {
        if ($encrypted == TRUE && !isset($this->_publicKey)) {
            echo "Must provide service key for encryption";
            exit;
        } else {
            $this->_isEncryptedRequest = $encrypted;
        }
    }

    public function setEncryptedResponse($encrypted)
    {
        if ($encrypted == TRUE && !isset($this->_privateKey)) {
            echo "Must provide private key for decryption";
            exit;
        } else {
            $this->_isEncryptedResponse = $encrypted;
        }
    }

    function __doRequest($request, $location, $saction, $version, $oneWay = 0)
    {
        //==============CREATE REQUEST MESSAGE=====================================================
        $doc = new DOMDocument('1.0');
        $doc->loadXML($request);

        $objWSSE = new WSSESoap($doc);
        // Add Timestamp with no expiration timestamp
        $objWSSE->addTimestamp();
        //===============END CREATE REQUEST



        //==============SIGN MESSAGE USE PRIVATE KEY=============================================
        // Create new XMLSec Key using RSA SHA-1 and type is private key
        // This algorithm is used to convert result of canonicalization algorithm into Signature value
        // RSA- key dependent algorithm and SHA1 - hash/dighest algorithm to create digest value
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));

        // Load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE)
        $objKey->loadKey($this->_privateKey, TRUE);

        // Sign the message - also signs appropraite WS-Security items
        $objWSSE->signSoapDoc($objKey);

        // Add certificate (BinarySecurityToken) to the message and attach pointer to Signature this certificate will be check by server
        $token = $objWSSE->addBinaryToken(file_get_contents($this->_cert));

        // Add pointer to signature
        $objWSSE->attachTokentoSig($token);
        //==============END SIGN MESSAGE==========================================================



        //==============ENCRYPT MESSAGE USING PUBLIC KEY==========================================
        if ($this->_isEncryptedRequest == TRUE) {
            // This algorithm (Symmetric Encoding Algoritm) AES128_CBC used to encrypt message data (in fixed size)
            $objKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
            $objKey->generateSessionKey(); //generate random pseudo bytes or Initialization vectors for CBC algorithm
            // RSA_1_5 128bits (detect this in WSDL file) - using
            // Public key encryption algorithm - Key Transport algorithm - this called Key Encryption Algorithm
            $siteKey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type' => 'public'));
            // Extract x509 certificate and extract public key from key file using openssl
            $siteKey->loadKey($this->_publicKey, TRUE, TRUE);
            // Attach x509 cert from public key as a binary token (for checking on server)
            $tokenX509 = $objWSSE->addBinaryToken(file_get_contents($this->_publicKey));
            // Now encrypt the message
            $objWSSE->encryptSoapDoc($siteKey, $objKey, null, TRUE, $tokenX509);
        }
        //================END ENCRYPTION PART=====================================================



        //================CALL WEBSERVICE METHODS AND DECRYPT RESPONSE MESSAGE IF IS SET==========
        $retVal = parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version, $oneWay = 0);
        //================END CALL WEBSERVICE METHODS=============================================


        //================DECRYPT MESSAGE IF NEEDED===============================================
        if ($this->_isEncryptedResponse == TRUE) {
            $doc = new DOMDocument('1.0');
            $doc->loadXML($retVal);
            $options = array("keys" => array("private" => array("key" => $this->_privateKey, "isFile" => TRUE, "isCert" => FALSE)));
            $objWSSE->decryptSoapDoc($doc, $options);
            $retVal = $doc->saveXML();
        }
        //================END DECRYPT MESSAGE IF NEEDED===============================================

        return $retVal;
    }


    // Logger
    private $_logger;

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }
}