<?php
class BaseController {
    private $lean;
    private $moduleName;

    public function __construct()
    {
        $this->lean = Lean::getInstance();
    }

    public function setError($error, $extraValues = null)
    {
        if ((is_array($error) && count($error)) || $error) {
            $this->lean->setControllerError($error, $extraValues);
        }
    }

    public function getDomain( $domainFolder, $domainName )
    {
        $className = '';
        $names = explode('-', $domainName);
        foreach ($names as $nombre) {
            $className .= ucfirst($nombre);
        }
        return $this->lean->getDomain(null, $domainFolder . '/' . $domainName . '.domain:' . ucfirst($className) . 'Domain');
    }

    public function response( $data = null)
    {
        $this->lean->setData('data', $data);
    }

    public function getConfig( $key = null ) {
        return $this->lean->getConfig( $key );
    }

    public function log( $event ) {
        $this->lean->getLogger()->log( 4, $event );
    }

    public function checkPolicies( $module, $fileName, $key, $data ) {
        $this->lean->checkPolicies( $module, $fileName, $key, $data );
    }
}
