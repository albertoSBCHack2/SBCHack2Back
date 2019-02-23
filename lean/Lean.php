<?php

require_once _BASE_CONTROLLER;
require_once _BASE_DOMAIN;
require_once _BASE_MODEL;
require_once _ROUTER;
require_once _RESPONSE;
require_once _DBACCESS;

/*
    Errors types:
        1. 	Aplicación
        2. 	Reglas de negocio
        3. 	Base de datos
        4. 	Primary Key Constraint
        5. 	Foreign key Constraint
        6. 	Unique Constraint
        7. 	Connection
        8. 	Developer
        9. 	Dump
        10. Authorization
        11. Controller Validation
        12. Router

    Status code:
        200. OK
        404. Not Found
        405. Method not Allowed
        500. Internal Server Error
*/

class Lean {
    public static $instance;
    private $config;
    private $environment;
    private $debug = false;
    private $params;
    private $router;
    private $response;
    private $logger;
    private $message = '';
    private $isBROk = true;
    private $errorsType = array(
        'General Error',
        'Business Logic',
        'Database',
        'Primary Key Constraint',
        'Foreign Key Constraint',
        'Unique Constraint',
        'Connection',
        'Developer',
        'Dump',
        'Authorization',
        'Application Logic',
        'Router'
    );
    private $errorType = null;
    private $managedErrorByDev = false;
    private $isManagedErrorByLean = true;
    private $data = array();
    private $customMessages = array(
        'pk' => array(),
        'fk' => array(),
        'uq' => array()
    );
    private $answered = false;
    private $controllers = array();
    private $services = array();
    private $models = array();
    private $envelope = false;
    private $responseModule = null;
    private $language;
    private $onTokenInvalid = null;
    private $secretString;
    private $isXHR = false;
    private $isRESTful = true;

    private function __construct( $config ) {
        $oLM = $this;
        $this->config = $config;
        $this->debug = isset( $config['debug'] ) && $config['debug'];
        $this->isRESTful = isset( $this->config['RESTful'] ) ? $this->config['RESTful'] : true;
        $this->setEnv( $this->config['environment'] ? $this->config['environment'] : 'production' ); // Definimos el ambiente.
    }

    // $instance SETTER/GETTER;
    public static function getInstance( $config = null ) {
        if( !self::$instance instanceof self ) {
            self::$instance = new self( $config );
        }

        return self::$instance;
    }

    // $config SETTER/GETTER;
    public function getConfig($key = null) {
        return $key ? ( isset( $this->config[$key] ) ? $this->config[$key] : null ) : $this->config;
    }

    public function setConfig($value) {
        $this->config = $value;
    }

    // $modules SETTER/GETTER;
    public function getModules() {
        return $this->config['modules'];
    }

    // $params SETTER/GETTER;
    public function getParams() {
        return $this->params;
    }

    //Init.
    public static function init( $config ) {
        try {
            $lean = self::getInstance( $config );

            if( isset( $config['timeZone'] ) && $config['timeZone'] ) {
                date_default_timezone_set( $config['timeZone'] );
            }

            //Cacharemos los errores para manipularlos.
            register_shutdown_function(function() use( $lean ){
                if( ( $error = error_get_last() ) ) {
                    if( $lean->response ) {
                        $lean->setManagedErrorByDev(false);
                        $lean->isManagedErrorByLean = false;
                        $lean->response->setHttpStatus( isset( $lean->isRESTful ) && $lean->isRESTful ? 500 : 200 );
                        $lean->setException( $error['message'], $error['file'], $error['line'] );
                        $lean->response->send();
                    }
                }
            });
        } catch( Exception $e ) {
            $message = $this->response->getError() && $this->response->getError()['message'] ? $this->response->getError()['message'] : $e->getMessage();
            self::$instance->setException( $message, $e->getFile(), $e->getLine() );
        }

        return self::$instance;
    }

    public function run() {
        ob_start();

        try {
            $success = true;
            self::$instance->isXHR = true;
            self::$instance->router = new Router($this);
            self::$instance->response = new Response($this);
            self::$instance->logger = new Logger( $this );
			self::$instance->logger->setInDate(); //Registramos la entrada del request.
            self::$instance->router->init();
            self::$instance->setResponseModule( self::$instance->router->getModule() );

            //Definimos el lenguaje.
            self::$instance->language = $this->loadLanguage();

            $data = self::$instance->router->run();
            self::$instance->response->setData($data);
        } catch( Exception $e ) {
            $success = false;
            $message = $this->response->getError() && $this->response->getError()['message'] ? $this->response->getError()['message'] : $e->getMessage();
            self::$instance->setException( $message, $e->getFile(), $e->getLine() );
        }

        DBAccess::closeConnections( !error_get_last() && $success );
        return self::$instance->response->send();
    }

    public function runController( $module, $controllerFile, $function, $params = [] ) {
        try {
            $success = true;
            self::$instance->isXHR = false;
            self::$instance->response = new Response($this);
            self::$instance->logger = new Logger( $this );
            self::$instance->setResponseModule( $module );

            //Registramos la entrada del request y el controller.
            self::$instance->logger->setInDate();
            self::$instance->logger->setController( $controllerFile . '->' . $function );
                
            $controller = $this->getController( $module, $controllerFile );
            $data = $controller->$function( $params );

            self::$instance->response->setData($data);
        } catch( Exception $e ) {
            $success = false;
            $message = $this->response->getError() && $this->response->getError()['message'] ? $this->response->getError()['message'] : $e->getMessage();
            self::$instance->setException( $message, $e->getFile(), $e->getLine() );
        }

        DBAccess::closeConnections( !error_get_last() && $success );
        return self::$instance->response->send();
    }

    // *************************************************************************
    // Error reporting
    // *************************************************************************

    private function setException( $messages, $file , $line, $log = true, $errorType = null ) {
        $errorType = is_null( $errorType ) ? ( $this->response->getError() ? $this->response->getError()['type'] : null ) : $errorType;
        $errorType = $errorType ? $errorType : $this->errorsType[0];

        if( !$this->managedErrorByDev ) {
            $error = [
                'errorType' => $errorType,
                'message' => $messages,
                'file' => null,
                'line' => null
            ];

            if( !$this->debug ) {
                $error['message'] = 'A problem has occurred. Try again in a few minutes.';
            } else if( !$this->isManagedErrorByLean ) {
                $error['file'] = $file;
                $error['line'] = $line;
            }
            
            $this->response->setError( $error['errorType'], $error['message'], $error['file'], $error['line'] );
        }

        //Registramos en el log.
        if( $log ) {
            $this->logger->log( 1, $messages, $errorType, $file, $line );
        }
    }

    private function setError($httpStatus, $errorType, $messages, $extraValues = null, $translate = true)
    {      
        // if( $translate ) {
        //     $message = $this->translate($message, $extraValues);
        // }

        if( $this->isXHR ) {
            $this->response->setHttpStatus($httpStatus);
        }

        $this->response->setError($this->errorsType[$errorType], $messages);

        throw new Exception( 'Lean Error!' );
    }

    //Método que establece que el error fue controlado por el framework.
    public function setManagedErrorByDev( $managedErrorByDev ) {
        $this->managedErrorByDev = $managedErrorByDev;
        $this->managedErrorByLean = !$managedErrorByDev;
    }

    public function getIsXHR() {
        return $this->isXHR;
    }

    public function getIsRESTful() {
        return $this->isRESTful;
    }

    public function getRouter() {
        return $this->router;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function setAppError($httpStatus, $message)
    {
        $this->setManagedErrorByDev(false);
        $this->setError($httpStatus, 0, $message);
    }

    public function setRouterError($httpStatus, $message)
    {
        $this->setManagedErrorByDev(false);
        $this->setError($httpStatus, 11, $message);
    }

    //Método para agregar errores de desarrollador.
    public function setDevError($message)
    {
        $this->setManagedErrorByDev(false);
        $this->setError(500, 7, $message);
    }

    //Método para agregar errores de tipo autorización o autenticación.
    public function setAuthError($message)
    {
        $this->setManagedErrorByDev(true);
        $this->setError(401, 9, $message);
    }

    public function setControllerError($message, $extraValues)
    {
        $this->setManagedErrorByDev(true);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 400 : 200 ), 10, $message, $extraValues );
    }

    public function setBRError( $messages, $extraValues = null )
    {
        $this->setManagedErrorByDev(true);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 405 : 200 ), 1, $messages, $extraValues );
    }

    //Método para agregar errores de conexión.
    public function setConnectionError($pMessage)
    {
        $this->setManagedErrorByDev(false);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 500 : 200 ), 6, $pMessage, null );
    }

    // *************************************************************************
    // Database errors
    // *************************************************************************

    //Método para cuando ocurre un error de tipo primary key.
    public function setPKMsg( $pPK = null, $pMessage = null, $pExtraValues = null ) {
        //Validate params.
        if( is_null( $pPK ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        }

        $this->customMessages['pk'][ $pPK ] = array(
            'message' => $pMessage
        );
    } 

    //Método para cuando ocurre un error de tipo unique.
    public function setUniqueCMsg( $pUniqueC = null, $pMessage = null, $pExtraValues = null ) {
        //Validate params.
        if( is_null( $pUniqueC ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        }


        $this->customMessages['uq'][ trim( $pUniqueC ) ] = array(
            'message' => $pMessage
        );
    } 

    //Método para establecer los errores de relaciones.
    public function setFKMsg( $pFK = null, $pMessage = null, $pExtraValues = null )
    {
        //Validate params.
        if( is_null( $pFK ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        }

        $this->customMessages['fk'][ trim( $pFK ) ] = array(
            'message' => $pMessage
        );
    }

    //Método para agregar errores de base de datos.
    public function setDBError( $pMessage = null, $pExtraValues = null )
    {
        $this->setManagedErrorByDev(false);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 500 : 200 ), 2, $pMessage, $pExtraValues );
    }

    //Método para agregar errores de primary key.
    public function setPKError( $pPK = null, $pMessage = null, $pExtraValues = null ) {
        $message = $pMessage;

        //Validate params.
        if( is_null( $pPK ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        } else {
            if( isset( $this->customMessages['pk'][ $pPK ] ) ) {
                $messageArr = $this->customMessages['pk'][ $pPK ];
                $message = $messageArr['message'];
            }
        }

        $this->setManagedErrorByDev(true);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 500 : 200 ), 3, $message );
    }

    //Función para agregar errores de relaciones.
    public function setFKError( $pFK = null, $pMessage = null, $pExtraValues = null ) {
        $message = $pMessage;

        //Validate params.
        if( is_null( $pFK ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        } else {
            if( isset( $this->customMessages['fk'][ $pFK ] ) ) {
                $messageArr = $this->customMessages['fk'][ $pFK ];
                $message = $messageArr['message'];
            }
        }

        $this->setManagedErrorByDev(true);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 500 : 200 ), 4, $message );
    }

    //Función para cuando ocurre un error de tipo unique.
    public function setUniqueCError( $pUniqueC = null, $pMessage = null, $pExtraValues = null ) {
        $message = $pMessage;

        //Validate params.
        if( is_null( $pUniqueC ) || is_null( $pMessage ) ) {
            $this->setDevError( 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.' );
        } else {
            if( isset( $this->customMessages['uq'][ $pUniqueC ] ) ) {
                $messageArr = $this->customMessages['uq'][ $pUniqueC ];
                $message = $messageArr['message'];
            } 
        }

        $this->setManagedErrorByDev(true);
        $this->setError( ( isset( $this->isRESTful ) && $this->isRESTful ? 500 : 200 ), 5, $message );
    }

    // *************************************************************************
    // File loaders
    // *************************************************************************

    public function loadJSONFile($path, $safe = false)
    {
        $fileContent = null;
        $fileStrings = glob( $path );

        if ($fileStrings) {
            $fileContent = json_decode( file_get_contents( $fileStrings[0] ), true );
        } elseif(!$safe) {
            $this->setManagedErrorByDev(false);
            $this->setError(500, 0, 'File "' . $path . '" not found.', null, false);
        }

        return $fileContent;
    }

    private function loadLanguagueFile()
    {
        $languages = $this->config['languages'] ?? [];
        $language = $this->config['defaults']['language'];
        if (in_array($this->language, $languages)) {
            $language = $this->language;
        }
        $basePath = '/strings'.'/'.$language.'.string.json';
        $filePath = _APP.$basePath;
        $appMessges = $this->loadJSONFile($filePath);
        $moduleDir = $this->router->getModuleDir() ?? [];
        if ($moduleDir) {
            $filePath = _MODULES.'/'.($moduleDir ?? '').$basePath;
        }
        $moduleMessges = $this->loadJSONFile($filePath, true) ?? [];
        return array_merge($appMessges, $moduleMessges);
    }

    // *************************************************************************
    // Language and Translations
    // *************************************************************************

    public function loadLanguage()
    {
        $language = null;
        if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
        {
            $langs = array_reduce(
                explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ), function( $res, $el ){
                    list( $l, $q ) = array_merge( explode( ';q=', $el ), [1] );
                    $res[$l] = (float) $q;
                    return $res;
                }, []);

            arsort( $langs, SORT_NUMERIC );

            //Extract most important (first)
            foreach( $langs as $lang => $val ){ break; }

            //If complex language simplify it
            if( stristr( $lang, '-' ) )
            {
                $tmpLang = explode( '-', $lang );
                $language = $tmpLang[0];
            }
        }

        return $language;
    }

    private function translate($message, $extraValues)
    {
        $messages = is_array($message) ? $message : array($message);

        if( isset( $this->config['translate'] ) && $this->config['translate'] ) {
            $strings = null;

            foreach ($messages as &$msg) {
                // Check if the message needs to be translated
                if( preg_match( '/^{/', $msg ) && preg_match( '/}$/', $msg ) ) {
                    if (!$strings) {
                        $strings = $this->loadLanguagueFile();
                    }

                    $key = str_replace('{', '', $msg);
                    $key = str_replace('}', '', $key);

                    $value = $strings[$key] ?? null;

                    if ($value) {
                        $msg = str_replace( '{' . $key . '}', $value, $msg );
                    } else {
                        $this->setDevError('Var \''.$key.'\' not found in strings file.');
                    }

                    if( $extraValues ) {
                        foreach( $extraValues as $key => $value ) {
                            $msg = str_replace( '{' . $key . '}', $value, $msg );
                        }
                    }
                }
            }
        }

        return is_array($message) ? $messages : $messages[0];
    }

    //Método para definir el módulo que debe dar respuesta.
    public function setResponseModule( $pResponseModule )
    {
        $this->responseModule = $pResponseModule;
    }

    //Método para regresar el módulo que debe dar respuesta.
    public function getResponseModule()
    {
        return $this->responseModule;
    }

    //Método para establecer el ambiente.
    public function setEnv( $pEnvironment )
    {
        $this->environment = strtolower( trim( $pEnvironment ) );

        if( $this->debug ) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
    }

    //Método para obtener el ambiente.
    public function getEnv()
    {
        return $this->environment;
    }

    //Método para obtener el request verb.
    public function getRequestVerb()
    {
        return $this->router->getRequest()->getVerb();
    }

    //Método para obtener el lenguaje.
    public function getLanguage()
    {
        return $this->language;
    }

    //Método para instanciar los Controllers.
    public function getController( $pModule, $pController, $pIdInstance = null, $pParams = null )
    {
        $controllerArr = explode( '.', $pController );
        $module = $pModule;
        $params = $pParams;

        $controllerArr = explode( ':', $pController );
        $file = $pController;
        $class = $pController;
        $id = $pController;

        if( count( $controllerArr ) > 1 )
        {
            $file = trim( $controllerArr[0] );
            $class = trim( $controllerArr[1] );
            $id = trim( $controllerArr[1] );
        }

        //Validate id for class.
        if( !is_null( $pIdInstance ) )
        {
            //Si el "$pId" viene como array, significa que se usará como los parámetros.
            if( is_array( $pIdInstance ) )
                $params = $pIdInstance;
            else
                $id .= $pIdInstance;
        }

        //Si no existe, la instanciamos.
        if( !isset( $this->services[ $id ] ) )
        {
            $path = ( $module ? _MODULES . '/' . $module : _APP ) . '/controllers/' . $file . '.php';

            if( glob( $path ) ) {
                require_once ( $module ? _MODULES . '/' . $module : _APP ) . '/controllers/' . $file . '.php';

                if( !is_null( $params ) )
                    $this->services[ $id ] = new $class( $params );
                else
                    $this->services[ $id ] = new $class();
            } else {
                $this->setDevError('Controller file not found.');
            }
        }

        return $this->services[ $id ];
    }

    //Método para instanciar los Dominios.
    public function getDomain( $pModule, $pDomain, $pIdInstance = null, $pParams = null )
    {
        $domainArr = explode( '.', $pDomain );
        $module = $pModule;
        $params = $pParams;

        $domainArr = explode( ':', $pDomain );
        $file = $pDomain;
        $class = $pDomain;
        $id = $pDomain;

        if( count( $domainArr ) > 1 )
        {
            $file = trim( $domainArr[0] );
            $class = trim( $domainArr[1] );
            $id = trim( $domainArr[1] );
        }

        //Validate id for class.
        if( !is_null( $pIdInstance ) )
        {
            //Si el "$pId" viene como array, significa que se usará como los parámetros.
            if( is_array( $pIdInstance ) )
                $params = $pIdInstance;
            else
                $id .= $pIdInstance;
        }

        //Si no existe, la instanciamos.
        if( !isset( $this->services[ $id ] ) )
        {
            $path = ( $module ? _MODULES . '/' . $module : _APP ) . '/domains/' . $file . '.php';

            if( glob( $path ) ) {
                require_once ( $module ? _MODULES . '/' . $module : _APP ) . '/domains/' . $file . '.php';

                if( !is_null( $params ) )
                    $this->services[ $id ] = new $class( $params );
                else
                    $this->services[ $id ] = new $class();
            } else {
                $this->setDevError( 'Domain file (' . $pDomain . ') not found.' );
            }
        }

        return $this->services[ $id ];
    }

    //Método para obtener acceso a la capa de datos.
    public function getModel( $pModule, $pModel, $idCnn = null )
    {
        $moduleArr = explode( '.', $pModel );
        $module = $pModule;

        $moduleArr = explode( ':', $pModel );
        $file = $pModel;
        $class = $pModel;
        $id = $pModel;

        if( count( $moduleArr ) > 1 )
        {
            $file = trim( $moduleArr[0] );
            $class = trim( $moduleArr[1] );
            $id = trim( $moduleArr[1] );
        }

        //Validate id for class.
        if( !is_null( $idCnn ) ) {
            $id .= $idCnn;
        }

        //Si no existe, la instanciamos.
        if( !isset( $this->models[ $id ] ) )
        {
            require_once ( $module ? _MODULES . '/' . $module : _APP ) . '/models/' . $file . '.php';

            $this->models[ $id ] = new $class( $idCnn );
        }

        return $this->models[ $id ];
    }

    //Método para establecer información.
    public function setData( $pIndex, $pData )
    {
        $this->data[ $pIndex ] = $pData;
    }

    //Método para obtener la información.
    public function getData( $pIndex = null, $pDelete = true )
    {
        if( is_null( $pIndex ) )
        {
            $data = $this->data;

            if( $pDelete )
                $this->data = array();
        }
        else
        {
            $data = $this->data[ $pIndex ];

            if( $pDelete )
                unset( $this->data[ $pIndex ] );
        }

        return $data ;
    }

    // Funcion para enviar une status https personalizado
    public function sendHttpStatus( $pStatusCode = null, $pMessage = null, $pExtraValues = null )
    {
        $message = $pMessage;

        //Validate params.
        if( is_null( $pStatusCode ) || is_null( $pMessage ) )
        {
            $this->errorType = 7;
            $message = 'Be sure to specify the correct parameters for the "' . __FUNCTION__ . '" method.';
        }
        else
        {
            http_response_code($pStatusCode);
            $this->errorType = 0;
            //Traducimos.
            // $message = $this->translate( $pModule, $pMessage, $pExtraValues );
        }

        //Provocamos el error.
        throw new Exception( $message );
    }

    //Method for get custom messages.
    public function getCustomMessages()
    {
        return $this->customMessages;
    }

    //Método para obtener los tipos de errores.
    public function getErrorType( $pIdx = null )
    {
        if( !is_null($pIdx) )
            return $this->errorsType[ $pIdx ];

        return $this->errorsType;
    }

    //Método para obtener el atributo onTokenInvalid.
    public function onTokenInvalid()
    {
        return $this->onTokenInvalid;
    }

    public function getSecretString()
    {
        return $this->secretString;
    }

    //Método para leer los archivos .json.
    private function getJSONFile( $pModule, $pResource, $pFileName )
    {
        $contentFile = null;
        $stringsFile = glob( ( $pModule ? _MODULES . '/' . $pModule : _APP ) . '/' . $pResource . '/' . $pFileName . '.json' );

        // var_dump(($pModule ? _MODULES . '/' . $pModule : _APP ) . '/' . $pResource . '/' . $pFileName . '.json'); die();

        if( count( $stringsFile ) === 1 )
            $contentFile = json_decode( file_get_contents( $stringsFile[0] ), true );
        else
        {
            //Validamos si existe el archivo o está duplicado.
            if( count( $stringsFile ) === 0 )
                $this->setDevError( 'File "' . $pFileName . '" is not found.' );
            else
                $this->setDevError( 'File "' . $pFileName . '" is duplicated.' );
        }

        return $contentFile;
    }

    //Método para validar las reglas.
    public function checkPolicies( $fileName, $data, $policyKey = null ) {
        $module = $this->getResponseModule();
        $rules = '';
        $policies = null;
        $policyRoutefile = _MODULES . '/' . $module . '/policies/' . $fileName . '.policy.php';

        if( count( glob( $policyRoutefile ) ) ) {
            $policies = require_once $policyRoutefile;
            
            if( $policyKey ) {
                if( isset( $policies[$policyKey] ) && is_array( $policies[$policyKey] ) ) {
                    $policies = $policies[$policyKey];
                } else {
                    $this->setDevError( 'Policies in "' . $policyKey . '" are not found, file: "' . $policyRoutefile . '", module: "' . $module . '".' );
                }
            }
        }

        if( is_null( $policies ) ) {
            $this->setDevError( 'Policy file (' . $policyRoutefile . ') not found in module "' . $module . '".' );
        }

        foreach( $policies as $keyRule => $valueRule ) {
            if( isset( $valueRule['constraint'] ) ) {
                $constraints = $valueRule['constraint'];
                $constraint = null;
                $value = isset( $data[ $keyRule ] ) ? $data[ $keyRule ] : null;
                $gtVal = null;
                
                //Apply trim.
                if(  is_string( $value ) ) {
                    $value = trim( $value );
                }

                //Loop through constraints.
                foreach( $valueRule['constraint'] as $keyConstraint => $valConstraint ) {
                    //Validate message to show.
                    $message =
                        isset( $valueRule['messages'] ) && isset( $valueRule['messages'][ $keyConstraint ] )
                            ? $valueRule['messages'][ $keyConstraint ]
                            : ( isset( $valueRule['message'] )
                                ? $valueRule['message']
                                : null );
                                
                    switch( $keyConstraint ) {
                        case 'required':
                            if( $valConstraint && ( is_null( $value ) || $value === '' ) ) {
                                $rules = $message;
                            }

                            break;

                        case 'gt':
                            if( is_null( $value ) || !is_numeric( $value ) || !( $value * 1 > $valConstraint ) ) {
                                $rules = str_replace( '{gt}', $valConstraint, $message );
                            }

                            break;

                        case 'gte':
                            if( is_null( $value ) || !is_numeric( $value ) || !( $value * 1 >= $valConstraint ) ) {
                                $rules = str_replace( '{gte}', $valConstraint, $message );
                            }

                            break;

                        case 'lt':
                            if( is_null( $value ) || !is_numeric( $value ) || !( $value * 1 < $valConstraint ) ) {
                                $rules = str_replace( '{lt}', $valConstraint, $message );
                            }

                            break;

                        case 'lte':
                            if( is_null( $value ) || !is_numeric( $value ) || !( $value * 1 <= $valConstraint ) ) {
                                $rules = str_replace( '{lte}', $valConstraint, $message );
                            }

                            break;

                        case 'email':
                            if( $valConstraint && ( $value && !filter_var( $value, FILTER_VALIDATE_EMAIL ) ) ) {
                                $rules = $message;
                            }

                            break;

                        case 'minLength':
                            if( $value && strlen( $value ) < $valConstraint ) {
                                $rules = $message;
                            }

                            break;

                        case 'maxLength':
                            if( $value && strlen( $value ) > $valConstraint ) {
                                $rules = $message;
                            }

                            break;
                    }
                }
            } else {
                $this->setDevError( 'It is neccesary to define the attribute "constraint" for the element: "' . $key . '".' );
            }

            if( $rules !== '' ) {
                break;
            }
        }

        if( $rules !== '' ) {
            $this->setBRError( $rules );
        }
    }
}
