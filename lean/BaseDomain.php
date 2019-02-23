<?php
    class BaseDomain
    {
        private $app;
        protected $moduleName;
        protected $modelName;
        protected $model;

        public function __construct()
        {
            $this->app = Lean::getInstance();

            if( $this->modelName )
                $this->model =  $this->manager->getModel( $this->moduleName, $this->modelName );
        }

        public function getConfig( $key = null ) {
            return $this->app->getConfig( $key );
        }

        public function setError($error, $extraValues = null)
        {
            if ((is_array($error) && count($error)) || $error) {
                $this->app->setBRError($error, $extraValues);
            }
        }

        public function getDomain( $dir, $file )
        {
            $className = '';
            $names = explode('-', $file);
            foreach ($names as $name) {
                $className .= ucfirst($name);
            }
            return $this->app->getDomain( null, $dir . '/' . $file . '.domain:' . ucfirst($className) . 'Domain' );
        }

        public function getModel( $dir, $file, $idCnn = null )
        {
            $className = '';
            $names = explode('-', $file);
            foreach ($names as $name) {
                $className .= ucfirst($name);
            }

            return $this->app->getModel( null, $dir . '/' . $file . '.model:' . ucfirst($className) . 'Model', $idCnn );
        }

        public function setPKMsg( $pPK = null, $pMessage = null, $pExtraValues = null ) {
            $this->app->setPKMsg( $pPK, $pMessage, $pExtraValues );
        }

        public function setFKMsg( $pFK = null, $pMessage = null, $pExtraValues = null ) {
            $this->app->setFKMsg( $pFK, $pMessage, $pExtraValues );
        }

        public function setUniqueCMsg( $pUniqueC = null, $pMessage = null, $pExtraValues = null ) {
            $this->app->setUniqueCMsg( $pUniqueC, $pMessage, $pExtraValues );
        }

        public function log( $event ) {
            $this->app->getLogger()->log( 4, $event );
        }
    }
?>
