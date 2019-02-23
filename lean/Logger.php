<?php
	/* 
		Logs type:
			1. Error
			2. Database
			3. Request
			4. Activity
	*/

    class Logger {
        private $lean;
        private $error;
        private $db;
        private $errors;
		private $directory;
		private $mongoConfig;
		private $dbCollection;
		private $errorsCollection;
		private $inDate;
		private $inController;

        //Construct.
        public function __construct( $lean ) {
            $this->lean = $lean;
            $this->logDBDir = 'db';
			$this->logErrorsDir = 'errors';
			$this->logRequestsDir = 'requests';
			$this->logActivityDir = 'activity';
			$this->loggerConfig = $this->lean->getConfig('logger');
			$this->logErrors = isset( $this->loggerConfig['logErrors'] ) ? $this->loggerConfig['logErrors'] : false;
			$this->logDB = isset( $this->loggerConfig['logDB'] ) ? $this->loggerConfig['logDB'] : false;
			$this->logRequests = isset( $this->loggerConfig['logRequests'] ) ? $this->loggerConfig['logRequests'] : false;
			$this->logActivity = isset( $this->loggerConfig['logActivity'] ) ? $this->loggerConfig['logActivity'] : false;
			$this->mongoConfig = isset( $this->loggerConfig['mongo'] ) ? $this->loggerConfig['mongo'] : null;
		}
		
		//Función que sirve como interfaz para loguear.
		public function log( $logType, $event = null, $errorType = null, $file = null, $line = null ) {
			try {
				if( $this->mongoConfig ) {
					$this->logToMongo( $logType, $event, $errorType, $file, $line );
				}
			} catch( MongoConnectionException $e ) {
			} catch( MongoException $e ) {
			} catch( Exception $e ) {
			}
		}

		//Función para registrar la fecha de entrada del request.
		public function setInDate() {
			$this->inDate = new DateTime('now', new DateTimeZone('UTC'));
		}
		
		
		//Función para registrar el controller para cuando no exista uri.
		public function setController( $controller ) {
			$this->inController = $controller;
		}
        
        //Función para escribir el log en MongoDB.
		private function logToMongo( $logType, $event = null, $errorType = null, $file = null, $line = null ) {
			if( 
				$this->mongoConfig && 
				( $logType === 1 && $this->logErrors ) || 
				( $logType === 2 && $this->logDB ) || 
				( $logType === 3 && $this->logRequests ) ||
				( $logType === 4 && $this->logActivity ) 
			) {
				if( isset( $this->mongoConfig['host'] )
					&& isset( $this->mongoConfig['db'] ) ) {
					//Los options no se van a loguear
					if( $this->lean->getIsXHR() === false || ( $this->lean->getIsXHR() && $this->lean->getRouter()->getRequest()->getVerb() != 'OPTIONS'  ) ) {
						$currentDate = new DateTime('now', new DateTimeZone('UTC'));
						$stringCnn = $this->mongoConfig['host'] . ':' . ( isset( $this->mongoConfig['port'] ) ? $this->mongoConfig['port'] : 27017 );
						$mongo = new MongoDB\Driver\Manager( $stringCnn );
						$bulk = new MongoDB\Driver\BulkWrite;
						$collection = null;
						$document = $logType === 4 ? ( is_array( $event ) ? $event : array('event' => $event) ) : array();
						$document['module'] = $this->lean->getResponseModule();
						$document['verb'] = $this->lean->getIsXHR() ? $this->lean->getRouter()->getRequest()->getVerb() : 'no-verb';
						$document['baseUri'] = $this->lean->getIsXHR() ? $this->lean->getRouter()->getRequest()->getBaseURI() : null;
						$document['uri'] = $this->lean->getIsXHR() ? $this->lean->getRouter()->getRequest()->getUri() : $this->inController;
						
						//Parámetros de entrada.
						if( $this->lean->getIsXHR() ) {
							$document['inParams'] = array(
								'headers' => $this->lean->getRouter()->getRequest()->getHeader(),
								'params' => $this->lean->getRouter()->getRequest()->getParams(),
								'body' => $this->lean->getRouter()->getRequest()->getBody(),
								'query' => $this->lean->getRouter()->getRequest()->getQuery()
							);
						}

						if( $logType === 1 || $logType === 2 || $logType === 4 ) {
							if( $logType === 1 || $logType === 2 ) {
								$document['event'] = $event;
							}

							$document['createdAt'] = new MongoDB\BSON\UTCDateTime( $currentDate->getTimestamp() * 1000 );
						}
						
						if( $logType === 1 ) {
							$collection = $this->logErrorsDir;
							$document['errorType'] = $errorType;
							$document['file'] = $file;
							$document['line'] = $line;
						} else if( $logType === 2 ) {
							$collection = $this->logDBDir;
						} else if( $logType === 3 ) {
							$collection = $this->logRequestsDir;
							$document['inDate'] = new MongoDB\BSON\UTCDateTime( $this->inDate->getTimestamp() * 1000 );
							$document['outDate'] = new MongoDB\BSON\UTCDateTime( $currentDate->getTimestamp() * 1000 );
							$document['duration'] = $currentDate->getTimestamp() - $this->inDate->getTimestamp();
						} else if( $logType === 4 ) {
							$collection = $this->logActivityDir;
						}
						
						$bulk->insert( $document );
						$mongo->executeBulkWrite( $this->mongoConfig['db'] . '.' . $collection, $bulk );
					}
				} else {
					throw new Exception( 'Be sure to set the following properties for the logger mongo configuration: "host", "db" and "port" (port is optional).' );
				}
			}
        }
    }
?>