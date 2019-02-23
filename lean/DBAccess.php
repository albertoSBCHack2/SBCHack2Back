<?php
	/***
		Autor: Gil Verduzco
		Descripción: Clase para gestionar las conexiones.
	***/

	class DBAccess
	{
		//Propiedades de la clase.
		private static $instances = array();
		private static $cnns = array();
		private static $cache = array();

		private $lean = null;
		private $idCnn = null;
		private $cnnData = null;
		private $queryArray = array();
		private $tables;

		//Constructor.
		private function __construct( $params ) {
			$this->lean = Lean::getInstance();
			$this->idCnn = $params['idCnn'];
			$this->cnnData = $params['cnnData'];
		}

		//Método para obtener la instancia de conexión.
		public static function getInstance( $cnn = null ) {
			$lean = Lean::getInstance();
			$cnnData = self::setConnection( $lean, $cnn );

			if( !isset( self::$instances[ $cnnData['idCnn'] ] ) ) {
				self::$instances[ $cnnData['idCnn'] ] = new self( $cnnData );
			}

      		return self::$instances[ $cnnData['idCnn'] ];
		}

		//Método para establecer los datos de conexión.
		private static function setConnection( $lean, $cnn ) {
			$dbConnections = $lean->getConfig('dbConnections');

			//Validate dbConnections.
			if( !$dbConnections ) {
				$lean->setDevError( 'There is not dbConnections in your db-connections.config file.' );
			}

			//Set id connection.
			if( is_null( $cnn ) ) {
				if( !( isset( $dbConnections['default'] ) && $dbConnections['default'] ) ) {
					$lean->setDevError( 'There is not default connection in your dataSource.' );
				}

				$cnn = $dbConnections['default'];
			}

			if( is_array( $cnn ) ) {
				//Validatemos que vengan definidas las propiedad principales.
				if( isset( $cnn['dbType'] )
					&& isset( $cnn['host'] )
					&& isset( $cnn['user'] )
					&& isset( $cnn['password'] )
					&& isset( $cnn['db'] ) ) {
					//Se crea un id de conexión dinámico. Debido a que el programador definió los datos de conexión en tiempo de ejecución.
					$cnn = 'leanDataSource_' . $cnn['dbType']
						. '_' . $cnn['host']
						. '_' . $cnn['db']
						. '_' . $cnn['user']
						. '_' . ( isset( $cnn['port'] ) ? $cnn['port'] : '' );

					$dbConnections = $cnn;
				} else {
					$lean->setDevError( 'Be sure to set the following properties for the "getInstance" method: "dbType", "host", "user", "password" and "db".' );
				}
			} else if( is_string( $cnn ) ) {
				if( isset( $dbConnections['dataSource'] ) && $dbConnections['dataSource'] ) {
					//Validamos si la conexión está registrada.
					if( !isset( $dbConnections['dataSource'][ $lean->getEnv() ][ $cnn ] ) ) {
						$lean->setDevError( 'The database identifier (' . $cnn . ') is not registered for the environment "' . $lean->getEnv() . '".' );
					}
				}
			} else {
				$lean->setDevError( 'Incorrect parameter for "getInstance". Must receive a "string" or an "array".' );
			}

			//Obtenemos los datos de conexión
			return array(
				'idCnn' => $cnn,
				'cnnData' => $dbConnections['dataSource'][ $lean->getEnv() ][ $cnn ]
			);
		}

		public function setTables($tables)
		{
			$this->tables = $tables;
		}

		//Método para abrir las conexiones.
		private function openConnection()
		{
			try
			{
				//Connections are Singleton.
				if( !( isset( self::$cnns[ $this->idCnn ] ) && isset( self::$cnns[ $this->idCnn ]['oCnn'] ) ) )
				{
					if( $this->cnnData['dbType'] === 'MySQL' || $this->cnnData['dbType'] === 'PostgreSQL' || $this->cnnData['dbType'] === 'SQLServer' )
					{
						self::$cnns[ $this->idCnn ] = array();

						//Validamos si el programador definió el connctionsString.
						if( isset( $this->cnnData['connStr'] ) && trim( $this->cnnData['connStr'] ) )
						{
							$connStr = $this->cnnData['connStr'];
							$connStr = str_replace( '{db}', $this->cnnData['db'], $connStr );
							$connStr = str_replace( '{host}', $this->cnnData['host'], $connStr );
							$connStr = str_replace( '{port}', $this->cnnData['port'], $connStr );
						}
						else
						{
							$connStr = ( $this->cnnData['dbType'] === 'MySQL'
									? 'mysql'
									: ( $this->cnnData['dbType'] === 'PostgreSQL' ? 'pgsql' : 'sqlsrv' )
								)
								. ':' . ( $this->cnnData['dbType'] === 'SQLServer' ? 'Database' : 'dbname' ) . '=' . $this->cnnData['db']
								. ';' . ( $this->cnnData['dbType'] === 'SQLServer' ? 'Server' : 'host' ) . '=' . $this->cnnData['host'];

							//Validate port.
							if( isset( $this->cnnData['port'] ) && trim( $this->cnnData['port'] ) )
							{
								if( $this->cnnData['dbType'] === 'SQLServer' )
									$connStr .= ',' . $this->cnnData['port'];
								else
									$connStr .= ';port=' . $this->cnnData['port'];
							}
						}

						self::$cnns[ $this->idCnn ]['oCnn'] = new \PDO( $connStr, $this->cnnData['user'], $this->cnnData['password'] );
						self::$cnns[ $this->idCnn ]['stmt'] = null;

						if( !self::$cnns[ $this->idCnn ]['oCnn'] )
						{
							$this->lean->setConnectionError( 'Failed to connect to database "' . $this->cnnData['db'] . '".' );
						}

						self::$cnns[ $this->idCnn ]['oCnn']->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

						//SQLServer no soporta esto.
						if( $this->cnnData['dbType'] === 'MySQL' || $this->cnnData['dbType'] === 'PostgreSQL' )
							self::$cnns[ $this->idCnn ]['oCnn']->setAttribute( \PDO::ATTR_EMULATE_PREPARES, true );

						if( isset( $this->cnnData['charset'] ) && version_compare( PHP_VERSION, '5.3.6', '<' ) )
							self::$cnns[ $this->idCnn ]['oCnn']->setAttribute( \PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES '" . $this->cnnData['charset'] . "'" );
					}
					else
					{
						$this->lean->setConnectionError( 'Unsupported database engine (' . $this->cnnData['dbType'] . ').' );
					}
				}

				//Begin transaction.
				$this->begin();
			}
			catch( \PDOException $e )
			{
				$this->lean->setConnectionError( 'Error connecting to database "' . $this->idCnn . '.' . $this->cnnData['db'] . '". (' . utf8_encode( $e->getMessage() ) . ')' );
			}
		}

		//Método para obtener las conexiones existentes.
		public static function getConnections()
		{
			return self::$cnns;
		}

		//Método para obtener el parámetro actual.
		private function getCurrentParam( $pParams, $pCurrentValue, $pCurrentValueExtra, $pSPPrefix, $pRoutine )
		{
			$currentValue = $pCurrentValue;
			$isDefined = false;
			$param = array(
				'value' => null,
				'cast' => ''
			);

			//Validamos si el valor viene definido con el prefijo o sin el prefijo.
			if( !isset( $pParams[ $currentValue ] ) ) { 
				$currentValue = preg_replace( '/' . preg_quote( $pSPPrefix, '/' ) . '/', '', $pCurrentValue, 1 );
			}

			if( isset( $pParams[ $currentValue ] ) ) {
				$isDefined = true;
				$param['value'] = $pParams[ $currentValue ];
			}

			// if( $isDefined && !is_null( $pParams[ $currentValue ] ) ) {
			// 	$isDefined = true;
			// } else {
			// 	$isDefined = false;
			// }

			//Validamos si viene definido el parámetro.
			if( $isDefined ) {
				//Validamos que sea un valor válido.
				if( is_array( $param['value'] ) || is_object( $param['value'] ) ) {
					$this->lean->setDevError( 'The param "' . $currentValue . '" for the routine "' . $pRoutine .'" is invalid.' );
				}

				$param['value'] = is_bool( $param['value'] ) ? ( $param['value'] ? 1 : 0 ) : trim( $param['value'] );

				if( $pCurrentValueExtra !== '' && $this->cnnData['dbType'] === 'PostgreSQL' ) {
					$castResponse = $this->validateCast( $pCurrentValueExtra, $currentValue, null );
					
					if( $castResponse ) {
						$param['cast'] = '::' . $pCurrentValueExtra;
					}
				}
			}

			return $param;
		}

		//Método para ejecutar queries.
		private function execute( $pQuery, $pIUD = false, $pLog = false, $pObj = true )
		{
			$this->openConnection();

			try {
				//Escribimos en el log.
				if( $pLog ) {
					$this->lean->getLogger()->log( 2, is_array( $pQuery ) ? $pQuery['query'] . ' | ' . implode( ',', $pQuery['bindParams'] ) : $pQuery );
				}

				$query = array();

				if( is_array( $pQuery ) ) {
					self::$cnns[ $this->idCnn ]['stmt'] = self::$cnns[ $this->idCnn ]['oCnn']->prepare( $pQuery['query'] );
					self::$cnns[ $this->idCnn ]['stmt']->execute( $pQuery['bindParams'] );
				} else {
					self::$cnns[ $this->idCnn ]['stmt'] = self::$cnns[ $this->idCnn ]['oCnn']->prepare( $pQuery );
					self::$cnns[ $this->idCnn ]['stmt']->execute();
				}

				if( self::$cnns[ $this->idCnn ]['stmt'] ) {
					if( !$pIUD ) {
						if( $this->cnnData['dbType'] !== 'SQLServer' || ( $this->cnnData['dbType'] === 'SQLServer' && self::$cnns[ $this->idCnn ]['stmt']->columnCount() ) )
							$query = self::$cnns[ $this->idCnn ]['stmt']->fetchAll( $pObj ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC );
					}
				} else {
					$this->lean->setDBError( 'There is an error in your SQL statement. Please check your code.' );
				}
			} catch( \PDOException $e ) {
				$queryString = '';
				$queryValues = '';

				if( is_array( $pQuery ) ) {
					$queryString = $pQuery['query'];
					$queryValues = ' | ' . implode( ',', $pQuery['bindParams'] );
				} else {
					$queryString = $pQuery;
				}
				
				//Get error.
				$messageOrig = $e->getMessage();
				$message = strtoupper( $messageOrig );
				//Get custom messages.
				$customMessages = $this->lean->getCustomMessages();
				//Custom message key.
				$cmKey = null;
				//Error type.
				$errorType = null;

				//Search custom message.
				foreach( $customMessages as $keyCM => $valueCM )  { 
					foreach( $valueCM as $key => $value )  {
						if( preg_match( '/' . strtoupper( $key ) . '/i', $message ) ) {
							$errorType = $keyCM;
							$cmKey = $key;
							break;
						}
					}

					if( $cmKey ) {
						break;
					}
				}

				//Validate primary key for MySQL. MySQL do not register a name for primary
				if( $this->cnnData['dbType'] === 'MySQL' && !$errorType ) {
					if( preg_match( "/FOR KEY 'PRIMARY'/i" , $message ) && count( $customMessages['pk'] ) > 0 ) {
						foreach( $customMessages['pk'] as $key => $value )  {
							$errorType = 'pk';
							$cmKey = $key;
						}
					} else {
						$this->lean->setDBError( $messageOrig . ' -> ( ' . $this->idCnn . ' | ' . $queryString . $queryValues . ' )' );
					}
				}

				if( $errorType === 'pk' ) {
					$this->lean->setPKError( $cmKey, 'Duplicate Primary Key (' . $messageOrig . ')' );
				} else if( $errorType === 'fk' ) {
					$this->lean->setFKError( $cmKey, $messageOrig );
				} else if( $errorType === 'uq' ) {
					$this->lean->setUniqueCError( $cmKey, 'Duplicate Unique Constraint. (' . $messageOrig . ')' );
				} else {
					$this->lean->setDBError( $messageOrig . ' -> ( ' . $this->idCnn . ' | ' . $queryString . $queryValues . ' )' );
				}
			}

			return $query;
		}

		//Método para obtener el procedimiento almacenado.
		private function getProcedure( $pSP, $pTrhowException ) {
			$sp = null;

			switch( $this->cnnData['dbType'] ) {
				case 'MySQL':
					$querySP = $this->execute( 'SHOW CREATE PROCEDURE ' . $pSP );

					if( count( $querySP ) === 1 ) {
						$sp = $querySP[0]->{'Create Procedure'};
					}

					break;
				case 'PostgreSQL':
					$querySP = $this->execute( "
						SELECT proname, prosrc
						FROM pg_proc
						WHERE proname = '" . $pSP . "'", false
					);

					if( count( $querySP ) === 1 ) {
						$sp = $querySP[0]->prosrc;
					} else if( count( $querySP ) > 1 ) {
						$this->lean->setDevError( 'The "' . $pSP . '" function is declared more than once.' );
					}

					break;
				case 'SQLServer':
					$sp = '';
					$querySP = $this->execute( "SELECT * FROM sys.objects WHERE OBJECT_ID = OBJECT_ID('" . $pSP . "');", false );

					if( count( $querySP ) === 1 ) {
						$querySP = $this->execute( "EXEC sp_helptext '" . $pSP . "';", false );
					}

					foreach( $querySP as $key => $value ) {
						$sp .= '
							' . $value->Text;
					}

					break;
			}

			//Validamos si la rutina existe.
			if( $pTrhowException && count( $querySP ) === 0 ) {
				$this->lean->setDBError( 'The routine "' . $pSP . '" does not exist in the database "' . $this->idCnn . '.' . $this->cnnData['db'] . '".' );
			}

			return $sp;
		}

		//Método para cuando se desea crear un paginado.
		private function paginate( $pSP, $pSPParams, $pParams, $pIsQManager = false )
		{ 
			//Validamos que vengan definidos los parámetros correctos.
			if( !( isset( $pParams['_page'] ) && isset( $pParams['_rows'] )
				&& isset( $pParams['_slt'] ) ) ) {
				$this->lean->setDevError( 'Ensure that the following variables are defined: "_page", "_rows" and "_slt"' );
			}

			$select = '';
			$fromWhere = '';
			$sqlValuesArr = array(
				'query' => null,
				'bindParams' => array()
			);
			$spPrefix = isset( $this->cnnData['spPrefix'] ) ? $this->cnnData['spPrefix'] : '';

			if( isset( $pParams['_spPrefix'] ) ) {
				$spPrefix = $pParams['_spPrefix'];
			}

			if( $pIsQManager ) {
				$sp = trim( $pSP );
			} else {
				//Obtenemos el procedure.
				$sp = $this->getProcedure( $pSP, true );
			}

			$arrayConsulta = explode( '-- &', $sp );

			//Validamos si la rutina trae los comentarios requeridos.
			if( count( $arrayConsulta ) === 1 ) {
				$this->lean->setDBError( 'It is necessary to indicate in the routine "' . $pSP . '" the required comment (-- &) in order to be able to page it.' );
			}

			//Get "select" and "from".
			if( $pIsQManager ) {
				for( $x=0; $x<count($arrayConsulta); $x++ ) {
					if( $arrayConsulta[$x] !== '' ) {
						if( $select === '' ) { 
							$select = $arrayConsulta[$x]; 
						} else { 
							$fromWhere = $arrayConsulta[$x]; 
							break; 
						}
					}
				}
			} else {
				$select = $arrayConsulta[1];
				$fromWhere = $arrayConsulta[2];
			}

			//Remplazamos los parámetros.
			if( $pSPParams ) {
				foreach ( $pSPParams as $key => $value ) {
					$currentValueArray = array();
					$currentValueExtra = '';
					
					//Validamos si el valor es un array.
					if( is_array( $value ) ) {
						$this->lean->setDevError( 'The value "' . $key . '" must not be an array.' );
					} else {
						$currentValue = trim( $value );
						
						if( $this->cnnData['dbType'] === 'PostgreSQL' ) {
							$currentValueArray = explode( '::', $value );
							$currentValue = trim( $currentValueArray[0] );
							$currentValueExtra = count( $currentValueArray ) === 1 ? '' : trim( $currentValueArray[1] );
						}

						//Obtenemos el parámetro actual.
						$paramsArr = $this->getCurrentParam( $pParams, $currentValue, $currentValueExtra, $spPrefix, $pSP );

						//Detectamos cuántas veces se encuentra repetido este parámetro.
						//Esto se hace sólo para SQLServer porque no soporta "ATTR_EMULATE_PREPARES".
						if( $this->cnnData['dbType'] === 'SQLServer' ) {
							$currentValueAux = '@' . $currentValue;
							$fromWhereArr = explode( $currentValueAux, $fromWhere );
							$fromWhereArrCount = sizeof( $fromWhereArr );

							if( $fromWhereArrCount > 0 ) {
								for( $x=0; $x<$fromWhereArrCount - 2; $x++ ) {
									$newParam = 'parameterNameChange' . rand(0,1000000);
									$fromWhere = preg_replace( '/' . preg_quote( $currentValueAux, '/' ) . '/', $newParam, $fromWhere, 1 );
									//Reemplazamos.
									$fromWhere = str_replace( $newParam, ':' . $newParam . $paramsArr['cast'], $fromWhere );
									$sqlValuesArr['bindParams'][$newParam] = $paramsArr['value'];
								}
							}

							//Se cambian todos los parámetros que se repiten excepto el úlitmo.
							//Por eso se necesita contemplar el parámetro de manera original.
							$sqlValuesArr['bindParams'][$currentValue] = $paramsArr['value'];
							$fromWhere = str_replace( $currentValueAux, ':' . $currentValue . $paramsArr['cast'], $fromWhere );
							//Quitamos el arrova de los parámetros.
							$fromWhere = str_replace( '@:', ':', $fromWhere );
						} else {
							//Validar que exista el parámetro.
							$paramCount = sizeof( explode( $currentValue, $fromWhere ) ) - 1;

							if( $paramCount > 0 ) {
								//Reemplazamos.
								$bindParameter = ':' . $currentValue;
								$fromWhere = str_replace( $currentValue, $bindParameter . $paramsArr['cast'], $fromWhere );
								$sqlValuesArr['bindParams'][$currentValue] = $paramsArr['value'];

								//Quitamos las dobles comillas en caso de ser PostgreSQL.
								if( $this->cnnData['dbType'] === 'PostgreSQL' ) {
									$fromWhere = str_replace( '"' . $bindParameter . '"', $bindParameter, $fromWhere );
								}
							}
						}
					}
				}
			}

			$fromWhere = str_replace( ';', '', $fromWhere );
			$sqlValuesArr['query'] = '
				SELECT COUNT(*) AS total_rows
				FROM(
					' . $select . ' ' .
					$fromWhere . '
				) tmp';

			//Obtenemos el total de registros.
			$query = $this->execute( $sqlValuesArr );

			$page = $pParams['_page'] * 1;
			$rows = $pParams['_rows'] * 1;
			$start = $rows * $page - $rows;
			$totalRows = isset( $query[0]->total_rows ) ? $query[0]->total_rows : 0;
			$pagShowExec = array();
			$varPR = null;

			//Validamos que _page y _rows sea mayor a cero.
			if( $page === 0 ) {
				$varPR = '_page';
			} else if( $rows === 0 ) {
				$varPR = '_rows';
			}

			if( $varPR ) {
				$this->lean->setDevError( 'The variable "' . $varPR . '" must be greater than zero' );
			}

			if( $totalRows > 0 ) {
				$totalPages = ceil( $totalRows / $rows );
			} else {
				$totalPages = 0;
			}

			if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] ) {
				$pagShowExec['query1'] = $sqlValuesArr['query'];
			}

			//Get orderBy or create.
			if( !isset( $pParams['_orderBy'] ) ) {
				$pParams['_orderBy'] = explode( ',', $pParams['_slt'] )[0];
			}

			//Obtenemos sólo los registros que requiere el paginado.
			if( $this->cnnData['dbType'] === 'SQLServer' ) {
				$sqlValuesArr['query'] = '
					SELECT ' . $pParams['_slt'] . '
					FROM(
						SELECT ROW_NUMBER() OVER( ORDER BY ' . $pParams['_orderBy'] . ') AS row_number, ' . $pParams['_slt'] . '
						FROM(
							' . $select . ' ' .
							$fromWhere . '
						) tmp
					) tmp
					WHERE tmp.row_number > ' . $start . '
					AND tmp.row_number <= ' . ( $rows * $page );
			} else {
				$sqlValuesArr['query'] = '
					SELECT ' . $pParams['_slt'] . '
					FROM(
						' . $select . ' ' .
						$fromWhere . '
						ORDER BY ' . $pParams['_orderBy'] . 
						(
							$this->cnnData['dbType'] === 'MySQL'
								?
									' LIMIT ' . $start . ', ' . $rows
								:
									' LIMIT ' . $rows . ' OFFSET ' . $start
						) . ' 
					) tmp';
			}

			if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] ) {
				$pagShowExec['query2'] = $sqlValuesArr['query'];
				$pagShowExec['bindParams'] = $sqlValuesArr['bindParams'];

				var_dump( $pagShowExec );
				die();
			}

			//Ejecutamos la consulta.
			$query = $this->execute( $sqlValuesArr, false, true );

			return array(
				'pageData' => $query,
				'page' => $page * 1,
				'pages' => $totalPages * 1,
				'totalRows' => $totalRows * 1
			);
		}

		//Método para ejecutar los sp.
		public function exec( $pSP = null, $pParams = null, $pHC = false )
		{
			//Si los parámetros vienen NULL, significa que usaremos el QManager.

			if( $pHC ) //Validamos si se va a ejecutar código duro.
			{
				$sqlValuesArr = array(
					'query' => $pSP,
					'bindParams' => array()
				);

				//Obtenemos sólo los parámetros que se van a bindear.
				foreach( $pParams as $key => $value )
				{
					$boundParams = explode( ':' . $key, $pSP );

					if( count( $boundParams ) > 1 )
						$sqlValuesArr['bindParams'][ $key ] = $value;
				}

				if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] )
				{
					var_dump( $sqlValuesArr );
					die();
				}

				return $this->execute( $sqlValuesArr, false, true, false );
			}
			else if( !is_null( $pSP ) && !is_null( $pParams ) ) //Llamado a store procedure o función.
			{
				$spArr = explode( '(', $pSP );
				$pSP = $spArr[0];

				$spParams = count( $spArr ) === 2 ? $spArr[1] : null; //Validamos si el programador definió los parámetros.
				$spParamsAux = array();
				$params = '';
				$count = 1;
				$total = 0;
				$spPrefix = isset( $this->cnnData['spPrefix'] ) ? $this->cnnData['spPrefix'] : '';

				if( isset( $pParams['_spPrefix'] ) )
					$spPrefix = $pParams['_spPrefix'];

				//Obtenemos los parámetros del store.
				//Validate cache.
				if( isset( self::$cache[ $this->idCnn ][ $pSP ] ) && is_null( $spParams ) )
					$spParams = self::$cache[ $this->idCnn ][ $pSP ];
				else if( is_null( $spParams ) )
				{
					//Primero, validamos si existe el sp de lean.
					$sp = $this->getProcedure( 'lean_get_params', false );

					//Segundo, validamos si existe el sp de la consulta.
					$this->getProcedure( $pSP, true );

					switch( $this->cnnData['dbType'] )
					{
						case 'MySQL':
							if( $sp )
							{
								$spParams = $this->execute( "CALL lean_get_params( '" . $pSP . "' )" );
							}
							else
							{
								$spParams = $this->execute( "
									SELECT param_list
									FROM mysql.proc
									WHERE db = '" . $this->cnnData['db'] . "'
									AND name = '" . $pSP . "';
								" );
							}

							$spParams = $spParams[0]->param_list;
							$spParams = str_replace( array( '`' ), array( '' ), $spParams );
							$spParams = explode( ',', $spParams );

							break;

						case 'PostgreSQL':
							if( $sp )
							{
								$spParams = $this->execute( "SELECT * FROM lean_get_params( '" . $pSP . "' )" );
							}
							else
							{
								$spParams = $this->execute( "
									SELECT pg_catalog.pg_get_function_identity_arguments( p.oid ) AS param_list
									FROM pg_catalog.pg_proc p

									INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace

									WHERE n.nspname = 'public'
									AND proname = '" . $pSP . "';
								" );
							}

							$spParams = $spParams[0]->param_list;
							$spParams = str_replace( '"', '', $spParams );
							$spParams = str_replace( array( '{', '}' ), array( '', '' ), $spParams );
							$spParams = explode( ',', $spParams );

							break;

						case 'SQLServer':
							if( $sp )
							{
								$spParams = $this->execute( "EXECUTE lean_get_params '" . $pSP . "'" );
							}
							else
							{
								$spParams = $this->execute( "
									SELECT REPLACE( name, '@', '' ) + ' ' + TYPE_NAME(user_type_id) AS param_list
									FROM sys.parameters
									WHERE OBJECT_ID = OBJECT_ID( '" . $pSP . "' );
								" );
							}

							break;
					}

					//Add to cache.
					self::$cache[ $this->idCnn ][ $pSP ] = $spParams;
				}
				else //Significa que el programador definió los parámetros del sp o función.
				{
					$spParams = str_replace( ')', '', $spParams );
					$spParams = explode( ',', $spParams );
				}

				//Validamos si el query es para un paginado.
				if( isset( $pParams['_paginate'] ) && $pParams['_paginate'] )
				{
					foreach( $spParams as $key => $value )
					{
						//Si viene como un objeto, significa que hay que obtener el valor del atributo param_list. (Esto pasa con SQLServer)
						if( is_object( $value ) && $this->cnnData['dbType'] === 'SQLServer' )
							$value = $value->param_list;

						$valueAux = trim( $value );

						if( $valueAux !== '' )
						{
							$arrayValue = explode( ' ', $valueAux );
							array_push( $spParamsAux, $arrayValue[0] );
						}
					}

					$spParams = $spParamsAux;
				}

				$total = count( $spParams );

				//Validamos si el query es para un grid.
				if( isset( $pParams['_paginate'] ) && $pParams['_paginate'] )
					return $this->paginate( $pSP, $spParams, $pParams );
				else
				{
					$sqlValuesArr = array(
						'query' => null,
						'bindParams' => array()
					);

					//Armamos los parámetros.
					foreach( $spParams as $key => $value )
					{
						//Si viene como un objeto, significa que hay que obtener el valor del atributo param_list. (Esto pasa con SQLServer)
						if( is_object( $value ) && $this->cnnData['dbType'] === 'SQLServer' ) {
							$value = $value->param_list;
						}

						$value = trim( $value );

						if( $value !== '' )
						{
							$currentValueArray = explode( ' ', $value );
							$currentValue = trim( $currentValueArray[0] );
							$currentValueExtra = count( $currentValueArray ) > 1 ? trim( $currentValueArray[1] ) : '';

							//Si no hay casteo, validamos si el programador estableció un casteo.
							if( !$currentValueExtra ) 
							{
								$currentValueArray = explode( '::', $value );
								$currentValue = trim( $currentValueArray[0] );
								$currentValueExtra = count( $currentValueArray ) === 1 ? '' : trim( $currentValueArray[1] );
							}

							if( $this->cnnData['dbType'] === 'PostgreSQL' && strtoupper( $currentValueExtra ) === 'CHARACTER' ) {
								$currentValueExtra = 'VARCHAR';
							}

							//Obtenemos el parámetro actual.
							$paramsArr = $this->getCurrentParam( $pParams, $currentValue, $currentValueExtra, $spPrefix, $pSP );
							$sqlValuesArr['bindParams'][$currentValue] = $paramsArr['value'];
							$params .= ':' . $currentValue . $paramsArr['cast'];

							if( $count < $total )
								$params .= ', ';

							$count++;
						}
					}

					//Ejecutamos.
					$exec = null;
					switch( $this->cnnData['dbType'] )
					{
						case 'MySQL':
							$exec = 'CALL ';
							break;
						case 'PostgreSQL':
							$exec = 'SELECT * FROM ';
							break;
						case 'SQLServer':
							$exec = 'EXECUTE ';
							break;
					}

					if( $this->cnnData['dbType'] === 'SQLServer' )
						$sqlValuesArr['query'] = $exec . $pSP . ' ' . $params;
					else
						$sqlValuesArr['query'] = $exec . $pSP . '( ' . $params . ' )';

					if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] )
					{
						var_dump( $sqlValuesArr );
						die();
					}

					return $this->execute( $sqlValuesArr, false, true, false );
				}
			}
			else
			{
				$this->lean->setDevError( 'Be sure to set the correct parameters for the "exec" method.' );
			}
		}

		//Método para hacer un insert.
		public function insert( $pTable = null, $pParams = null ) {
			//Validate if $pTable and "$pParams" are defined.
			if( is_null( $pTable ) || is_null( $pParams ) ) {
				$this->lean->setDevError( '(insert) Be sure to send the table name and required parameters.' );
			}

			//Obtenemos la tabla y el primary key consecutivo.
			$tableArray = explode( ':', $pTable );
			$table = trim( $tableArray[0] );
			$primaryKeyConsecutivo = count( $tableArray ) === 2 ? trim( $tableArray[1] ) : null;
			$sqlWhere = '';
			$sqlNextID = '';
			$sqlInsert = 'INSERT INTO ' . $table . '( ';
			$sqlValues = 'VALUES';
			$sqlValuesArr = array(
				'query' => null,
				'bindParams' => array()
			);
			$cast = array();
			$message = '(insert on table "' . $table . '") ';
			$nextID = array();
			$inserts = null;

			if( isset( $pParams['_cast'] ) ) {
				$cast = $pParams['_cast'];
			} else {
				$cast = $this->getColumnsToCast( $table );
			}

			//Validamos que venga definida la variable "_insert".
			if( !isset( $pParams['_insert'] ) ) {
				$pParams['_insert'] = $this->createArrayISW( $table, '_insert', $pParams );
			}

			$_insert = $pParams['_insert'];

			//Validamos si es un multi-insert.
			$inserts = !isset( $_insert[0] ) ? array( $_insert ) : $_insert;
			$insertCount = count( $inserts );

			//Validamos si debemos parsear un JSON.
			if( !is_array( $_insert ) ) {
				$_insert = json_decode( $_insert, true );

				if( is_null( $_insert ) ) {
					$this->lean->setDevError( $message . 'The "_insert" variable is not a correct JSON format.' );
				}
			}

			if( count( $_insert ) === 0 ) {
				$this->lean->setDevError( $message . 'The "_insert" variable should not be empty.' );
			}

			if( !is_null( $primaryKeyConsecutivo ) ) {
				//Validate cache.
				if( isset( self::$cache[ $this->idCnn ][ $table ]['primaryKey'] ) ) {
					$queryPrimaryKey = self::$cache[ $this->idCnn ][ $table ]['primaryKey'];
				} else {
					//Obtenemos los primary key de la tabla.

					//Primero, validamos si existe el sp.
					$sp = $this->getProcedure( 'lean_get_primary_key', false );

					if( $sp ) {
						$exec = null;
						$sp = 'lean_get_primary_key';
						$params = "'" . $this->cnnData['db'] . "', '" . $table . "'";

						switch( $this->cnnData['dbType'] ) {
							case 'MySQL':
								$exec = 'CALL ';
								break;
							case 'PostgreSQL':
								$exec = 'SELECT * FROM ';
								break;
							case 'SQLServer':
								$exec = 'EXECUTE ';
								break;
						}

						if( $this->cnnData['dbType'] === 'SQLServer' ) {
							$exec = $exec . $sp . ' ' . $params;
						} else {
							$exec = $exec . $sp . '( ' . $params . ' )';
						}

						$queryPrimaryKey = $this->execute( $exec, false );
					} else {
						$queryPrimaryKey = $this->execute( "
							SELECT c.column_name
							FROM information_schema.columns c

							INNER JOIN information_schema.key_column_usage kcu ON c.table_catalog = kcu.constraint_catalog
								AND c.table_name = kcu.table_name
								AND c.column_name = kcu.column_name

							INNER JOIN information_schema.table_constraints tc ON kcu.constraint_catalog = tc.constraint_catalog
								AND kcu.table_name = tc.table_name
								AND kcu.constraint_name = tc.constraint_name

							WHERE (
								( '" . $this->cnnData['dbType'] . "' = 'MySQL' AND c.table_schema = '" . $this->cnnData['db'] . "' ) OR
								( ( '" . $this->cnnData['dbType'] . "' = 'PostgreSQL' OR '" . $this->cnnData['dbType'] . "' = 'SQLServer' ) AND c.table_catalog = '" . $this->cnnData['db'] . "' )
							)
							AND c.table_name = '" . $table . "'
							AND tc.constraint_type = 'PRIMARY KEY'

							ORDER BY c.ordinal_position
						", false );
					}

					//Add to cache.
					self::$cache[ $this->idCnn ][ $table ]['primaryKey'] = $queryPrimaryKey;
				}

				//Armamos el where para el primary key consecutivo.
				for( $x=0; $x<$insertCount; $x++ ) {
					$cont = 0;
					$sqlWhere = '';

					foreach( $queryPrimaryKey as $row ) {
						if( strtoupper( $primaryKeyConsecutivo ) != strtoupper( $row->column_name ) ) {
							if( $cont === 0 ) {
								$sqlWhere .= 'WHERE ';
							} else {
								$sqlWhere .= '
								AND ';
							}

							//Validamos si está definido este campo.
							if( !isset( $_insert[ $row->column_name ] ) ) {
								$this->lean->setDevError( $message . 'The "' . $row->column_name . '" property is not defined in the parameters.' );
							}

							$value = $_insert[ $row->column_name ];
							$currentValue = is_null( $value ) ? $value : ( is_bool( $value ) ? ( $value ? 1 : 0 ) : trim( $value ) );
							//Validamos si este campo se debe castear.
							$castVal = $this->getCast( ':', $row->column_name, $cast );

							if( $this->cnnData['dbType'] === 'SQLServer' ) {
								$sqlWhere .= '[' . $row->column_name . '] ';
							} else {
								$sqlWhere .= $row->column_name;
							}

							//Validamos forma de casteo.
							if( $this->cnnData['dbType'] === 'PostgreSQL' || $castVal === '' ) {
								$sqlWhere .= ' = :' . $row->column_name . $castVal;
							} else {
								$sqlWhere .= ' = ' . $castVal;
							}

							$sqlValuesArr['bindParams'][ $row->column_name ] = $currentValue;
							$cont++;
						}
					}

					//Obtenemos el next id.
					$sqlValuesArr['query'] = '
						SELECT COALESCE( MAX( ' . $primaryKeyConsecutivo . ' ), 0 ) + 1 AS ' . $primaryKeyConsecutivo . '
						FROM ' . $table . '
						' . $sqlWhere . '
					';
					$queryNextID = $this->execute( $sqlValuesArr );

					//Armamos el insert del campo del primary key conescutivo.
					if( $this->cnnData['dbType'] === 'SQLServer' ) {
						$sqlInsert .= '[' . $primaryKeyConsecutivo . ']';
					} else {
						$sqlInsert .= $primaryKeyConsecutivo;
					}
					
					$sqlInsert .= ', ';
					array_push( $nextID, $queryNextID[0]->$primaryKeyConsecutivo );
				}

				$sqlValuesArr['query'] = null;
				$sqlValuesArr['bindParams'] = array();
			}

			//Armamos el insert de los demás campos.
			for( $x=0; $x<$insertCount; $x++ ) {
				$_insert = $inserts[$x];
				$count = 0;

				if( $x === 0 ) {
					$sqlValues .= '( ';
				} else {
					$sqlValues .= ', ( ';
				}

				//Consecutivo.
				if( !is_null( $primaryKeyConsecutivo ) ) {
					$sqlValues .= ':' . $x . '_' . $primaryKeyConsecutivo . ', ';
					$sqlValuesArr['bindParams'][ $x . '_' . $primaryKeyConsecutivo ] = $nextID[$x];
				}

				foreach( $_insert as $key => $value ) {
					//Validamos que el valor no sea un array.
					if( is_array( $value ) ) {
						$this->lean->setDevError(  $message . 'The "_insert" variable can not have nested values ​​of type "array".' );
					}

					//Obtenemos el valor actual.
					//Se debe validar si el valor viene NULL.
					$currentValue = is_null( $value ) ? $value : ( is_bool( $value ) ? ( $value ? 1 : 0 ) : trim( $value ) );
					//Validamos si este campo se debe castear.
					$castVal = $this->getCast( ':' . $x . '_', $key, $cast );

					if( $count > 0 ) {
						$sqlValues .= ', ';

						if( $x === 0 ) {
							$sqlInsert .= ', ';
						}
					}

					if( $x === 0 ) {
						if( $this->cnnData['dbType'] === 'SQLServer' ) {
							$sqlInsert .= '[' . $key . ']';
						} else {
							$sqlInsert .= $key;
						}
					}

					//Validamos forma de casteo.
					if( $this->cnnData['dbType'] === 'PostgreSQL' || $castVal === '' ) {
						$sqlValues .= ':' . $x . '_' . $key . $castVal;
					} else {
						$sqlValues .= $castVal;
					}

					$sqlValuesArr['bindParams'][ $x . '_' . $key ] = $currentValue;
					$count++;
				}

				$sqlValues .= ' )';
			}

			$sqlValuesArr['query'] =
				$sqlInsert . ' )
				' . $sqlValues . ';';

			if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] ) {
				var_dump( $sqlValuesArr );
				die();
			}

			//Ejecutamos el script.
			$this->execute( $sqlValuesArr, true );

			//Validamos si existe un identity.
			if( is_null( $primaryKeyConsecutivo ) ) {
				if( $this->cnnData['dbType'] === 'SQLServer' ) {
					$queryIdentity = $this->execute("
						SELECT NAME AS column_name
						FROM SYS.IDENTITY_COLUMNS
						WHERE OBJECT_NAME(OBJECT_ID) = '" . $table . "'
					");

					if( $queryIdentity ) {
						$primaryKeyConsecutivo = $queryIdentity[0]->column_name;
						$queryNextID = $this->execute("
							SELECT @@IDENTITY AS " . $primaryKeyConsecutivo . "
						");
					}
				}
			}

			if( !is_null( $primaryKeyConsecutivo ) ) {
				return $queryNextID[0]->$primaryKeyConsecutivo;
			}
		}

		//Método para hacer un update.
		public function update( $pTable = null, $pParams = null ) {
			//Validate if $pTable and "$pParams" are defined.
			if( is_null( $pTable ) || is_null( $pParams ) ) {
				$this->lean->setDevError( '(update) Be sure to send the table name and required parameters.' );
			}

			$message = '(update on table "' . $pTable . '") ';

			//Obtenemos la tabla.
			$table = trim( $pTable );
			$sqlSet = '
				SET ';
			$sqlWhere = '
				WHERE ';
			$sqlValuesArr = array(
				'query' => null,
				'bindParams' => array()
			);
			$cast = array();

			//Casteos.
			if( isset( $pParams['_cast'] ) ) {
				$cast = $pParams['_cast'];
			} else {
				$cast = $this->getColumnsToCast( $table );
			}

			//Validamos que venga definida la variable "_set".
			if( !isset( $pParams['_set'] ) ) {
				$pParams['_set'] = $this->createArrayISW( $table, '_set', $pParams );
			}

			$_set = $pParams['_set'];

			//Validamos si debemos parsear un JSON.
			if( !is_array( $_set ) ) {
				$_set = json_decode( $_set, true );

				if( is_null( $_set ) ) {
					$this->lean->setDevError( $message . 'The "_set" variable is not a correct JSON format.' );
				}
			}

			//Armamos el SET.
			$cont = 0;
			foreach( $_set AS $key => $value ) {
				//Validamos que el valor no sea un array.
				if( is_array( $value ) ) {
					$this->lean->setDevError(  $message . 'The "_set" variable can not have nested values ​​of type "array".' );
				}

				//Se debe validar si el valor viene NULL.
				$currentValue = is_null( $value ) ? $value : ( is_bool( $value ) ? ( $value ? 1 : 0 ) : trim( $value ) );
				//Validamos si este campo se debe castear.
				$castVal = $this->getCast( ':s_', $key, $cast );

				if( $cont > 0 ) {
					$sqlSet .= ',
					';
				}

				if( $this->cnnData['dbType'] === 'SQLServer' ) {
					$sqlSet .= '[' . $key . ']';
				} else {
					$sqlSet .= $key;
				}

				//Validamos forma de casteo.
				if( $this->cnnData['dbType'] === 'PostgreSQL' || $castVal === '' ) {
					$sqlSet .= ' = :s_' . $key . $castVal;
				} else {
					$sqlSet .= ' = ' . $castVal;
				}
				
				$sqlValuesArr['bindParams'][ 's_' . $key ] = $currentValue;
				$cont++;
			}

			//Validamos que venga definida la variable "where".
			if( !isset( $pParams['_where'] ) ) {
				$pParams['_where'] = $this->createArrayISW( $table, '_where', $pParams );
			}

			$_where = $pParams['_where'];

			//Validamos si debemos parsear un JSON.
			if( !is_array( $_where ) ) {
				$_where = json_decode( $_where, true );

				if( is_null( $_where ) ) {
					$this->lean->setDevError( $message . 'The "_where" variable is not a correct JSON format.' );
				}
			}

			//Armamos el WHERE.
			$cont = 0;
			foreach( $_where AS $key => $value ) {
				//Validamos que el valor no sea un array.
				if( is_array( $value ) ) {
					$this->lean->setDevError(  $message . 'The "_where" variable can not have nested values ​​of type "array".' );
				}

				$currentValue = is_null( $value ) ? $value : ( is_bool( $value ) ? ( $value ? 1 : 0 ) : trim( $value ) );
				//Validamos si este campo se debe castear.
				$castVal = $this->getCast( ':w_', $key, $cast );

				if( $cont > 0 ) {
					$sqlWhere .= '
						AND ';
				}

				if( is_null( $currentValue ) ) {
					if( $this->cnnData['dbType'] === 'SQLServer' ) {
						$sqlWhere .= '[' . $key . ']';
					} else {
						$sqlWhere .= $key;
					}

					$sqlWhere .= ' IS NULL';
				} else {
					if( $this->cnnData['dbType'] === 'SQLServer' ) {
						$sqlWhere .= '[' . $key . ']';
					} else {
						$sqlWhere .= $key;
					}

					//Validamos forma de casteo.
					if( $this->cnnData['dbType'] === 'PostgreSQL' || $castVal === '' ) {
						$sqlWhere .= ' = :w_' . $key . $castVal;
					} else {
						$sqlWhere .= ' = ' . $castVal;
					}

					$sqlValuesArr['bindParams'][ 'w_' . $key ] = $currentValue;
				}

				$cont++;
			}

			$sqlValuesArr['query'] =
				'UPDATE ' . $table
					. $sqlSet
					. $sqlWhere;

			if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] ) {
				var_dump( $sqlValuesArr );
				die();
			}
			
			//Ejecutamos el script.
			$this->execute( $sqlValuesArr, true );
		}

		//Método para hacer un update.
		public function delete( $pTable = null, $pParams = null ) {
			//Validate if $pTable and "$pParams" are defined.
			if( is_null( $pTable ) || is_null( $pParams ) ) {
				$this->lean->setDevError( '(delete) Be sure to send the table name and required parameters.' );
			}

			$message = '(delete on table "' . $pTable . '") ';

			//Obtenemos la tabla.
			$table = trim( $pTable );
			$sqlWhere = '
				WHERE ';
			$sqlValuesArr = array(
				'query' => null,
				'bindParams' => array()
			);

			if( isset( $pParams['_cast'] ) ) {
				$cast = $pParams['_cast'];
			} else {
				$cast = $this->getColumnsToCast( $table );
			}

			//Validamos que venga definida la variable "_where".
			if( !isset( $pParams['_where'] ) ) {
				$pParams['_where'] = $this->createArrayISW( $table, '_where', $pParams );
			}

			$_where = $pParams['_where'];

			//Validamos si debemos parsear un JSON.
			if( !is_array( $_where ) ) {
				$_where = json_decode( $_where, true );

				if( is_null( $_where ) ) {
					$this->lean->setDevError( $message . 'The "_where" variable is not a correct JSON format.' );
				}
			}

			//Armamos el WHERE.
			$cont = 0;
			foreach( $_where AS $key => $value ) {
				//Validamos que el valor no sea un array.
				if( is_array( $value ) ) {
					$this->lean->setDevError(  $message . 'The "_where" variable can not have nested values ​​of type "array".' );
				}

				$currentValue = is_null( $value ) ? $value : ( is_bool( $value ) ? ( $value ? 1 : 0 ) : trim( $value ) );
				//Validamos si este campo se debe castear.
				$castVal = $this->getCast( ':', $key, $cast );

				if( $cont > 0 ) {
					$sqlWhere .= '
						AND ';
				}

				if( is_null( $currentValue ) ) {
					if( $this->cnnData['dbType'] === 'SQLServer' ) {
						$sqlWhere .= '[' . $key . ']';
					} else {
						$sqlWhere .= $key;
					}

					$sqlWhere .= ' IS NULL ';
				} else {
					if( $this->cnnData['dbType'] === 'SQLServer' ) {
						$sqlWhere .= '[' . $key . ']';
					} else {
						$sqlWhere .= $key;
					}

					//Validamos forma de casteo.
					if( $this->cnnData['dbType'] === 'PostgreSQL' || $castVal === '' ) {
						$sqlWhere .= ' = :' . $key . $castVal;
					} else {
						$sqlWhere .= ' = ' . $castVal;
					}

					$sqlValuesArr['bindParams'][ $key ] = $currentValue;
				}

				$cont++;
			}

			$sqlValuesArr['query'] =
				'DELETE FROM ' . $table
					. $sqlWhere;

			if( isset( $pParams['_showExec'] ) && $pParams['_showExec'] ) {
				var_dump( $sqlValuesArr );
				die();
			}

			//Ejecutamos el script.
			$this->execute( $sqlValuesArr, true );
		}

		//Método para controlar la transacción.
		private function begin()
		{
			if( self::$cnns[ $this->idCnn ]['oCnn'] && !self::$cnns[ $this->idCnn ]['oCnn']->inTransaction() ) {
				self::$cnns[ $this->idCnn ]['oCnn']->beginTransaction();
			}
		}

		//Método para hacer commit.
		public static function commit( $pIdCnn = null ) {
			try {
				$cnns = $pIdCnn ? array( self::$cnns[ $pIdCnn ] ) : self::$cnns;

				foreach( $cnns as $cnn )
				{
					if( isset( $cnn['oCnn'] ) && $cnn['oCnn']->inTransaction() )
						$cnn['oCnn']->commit();
				}
			} catch( \PDOException $e ) {
				Lean::getInstance()->setConnectionError( 'Error on commit. (' . $e->getMessage() . ')' );
			}
		}

		//Método para hacer rollBack.
		public static function rollBack( $pIdCnn = null ) {
			try {
				$cnns = $pIdCnn ? array( self::$cnns[ $pIdCnn ] ) : self::$cnns;

				foreach( $cnns as $cnn )
				{
					if( isset( $cnn['oCnn'] ) && $cnn['oCnn']->inTransaction() )
						$cnn['oCnn']->rollBack();
				}
			} catch( \PDOException $e ) {
				Lean::getInstance()->setConnectionError( 'Error on rollback. (' . $e->getMessage() . ')' );
			}
		}

		//Método para cerrar las conexiónes.
		//Nos aseguramos de cerrar las conexiones y que se hagan los commit o rollBack necesarios.
		public static function closeConnections( $pCommit ) {
			try {
				foreach( self::$cnns as $cnn ) {
					if( isset( $cnn['oCnn'] ) && $cnn['oCnn']->inTransaction() ) {
						if( $pCommit ) {
							$cnn['oCnn']->commit();
						} else {
							$cnn['oCnn']->rollBack();
						}
					}

					$cnn['oCnn'] = null;
					$cnn['stmt'] = null;
				}
			} catch( \PDOException $e ) {
				Lean::getInstance()->setConnectionError( 'Error closing database connections. (' . $e->getMessage() . ')' );
			}
		}

		//Método para obtener el CAST que se le debe aplicar a los campos que lo requieren.
		private function getColumnsToCast( $pTable ) {
			$cast = array();

			//Validate cache.
			if( isset( self::$cache[ $this->idCnn ][ $pTable ]['cast'] ) ) {
				$columns = self::$cache[ $this->idCnn ][ $pTable ]['cast'];
			} else {
				//Leemos la estrucutra de la tabla para hacer los casteos necesarios.
				$columns = $this->execute( "
					SELECT column_name, data_type, character_maximum_length
					FROM information_schema.columns
					WHERE table_catalog = '" . $this->cnnData['db'] . "'
					AND table_name = '" . $pTable . "'
				" );

				//Add to cache.
				self::$cache[ $this->idCnn ][ $pTable ]['cast'] = $columns;
			}

			for( $x=0; $x<count($columns); $x++ ) {
				//Validamos el cast.
				$castResponse = $this->validateCast( $columns[$x]->data_type, $columns[$x]->column_name, $columns[$x]->character_maximum_length );

				if( $castResponse ) {
					$cast[ $columns[$x]->column_name ] = $castResponse;
				}
			}

			return $cast;
		}

		//Método para obtener la columna casteada.
		private function getCast( $pColumnPrefix, $pColumnName, $pColumnsToCast ) {
			$castVal = '';

			if( isset( $pColumnsToCast[ $pColumnName ] ) ) {
				if( $this->cnnData['dbType'] === 'MySQL' || $this->cnnData['dbType'] === 'SQLServer' ) {
					$castVal = 'CAST( ' . $pColumnPrefix . $pColumnName . ' AS ' . $pColumnsToCast[ $pColumnName ] . ' )';
				} else if( $this->cnnData['dbType'] === 'PostgreSQL' ) {
					$castVal .= '::' . $pColumnsToCast[ $pColumnName ];
				}
			}

			return $castVal;
		}

		//Método para validar si se debe aplicar el cast
		private function validateCast( $dataType, $columnName, $characterMaximumLength ) {
			$cast = null;

			switch( strtolower( $dataType ) ) {
				case 'boolean':
				case 'bit':
				case 'varchar':
				case 'character varying':
				case 'smallint':

					//MySQL truena al castear tipos de datos string.
					if(
						!( ( $dataType === 'varchar' || $dataType === 'character varying' ) && $this->cnnData['dbType'] === 'MySQL' ) ||
						$this->cnnData['dbType'] === 'PostgreSQL'
					) {
						$cast = $dataType . ( is_null( $characterMaximumLength ) ? '' : '(' . ( $characterMaximumLength < 0 ? 'MAX' : $characterMaximumLength ) . ')' );
					}
				default:
					break;
			}

			return $cast;
		}

		//Método para crear el array de "_insert", "_set" y "_where".
		private function createArrayISW( $pTable, $pISW, $pParams )
		{
			$pParamsAux = array();
			$prefix = '';

			switch( $pISW ) {
				case '_insert':
					$prefix = '_i-';
					break;
				case '_set':
					$prefix = '_s-';
					break;
				case '_where':
					$prefix = '_w-';
					break;
				default:
					break;
			}

			$messageISW = '(' . $pTable . ') You must specify the values ​​in a "' . $pISW . '" object. Or you can also specify them with the prefix "' . $prefix . '".';

			//Validamos si vienen definidos los parámetros por prefijo.
			if( $pParams && count( $pParams ) > 0 )
			{
				foreach( $pParams as $key => $value )
				{
					$realKey = explode( $prefix, $key );

					if( $realKey[0] === '' && count( $realKey ) === 2 )
						$pParamsAux[ $realKey[1] ] = $value;
				}

				if( count( $pParamsAux ) === 0 )
				{
					$this->lean->setDevError(  $messageISW );
				}
			}
			else
			{
				$this->lean->setDevError(  $messageISW );
			}

			return $pParamsAux;
		}

		//QManager.

		public function select( $pSelect = '*' )
		{
			array_push($this->queryArray, array(
				'type' => 'SELECT',
				'value' => ( is_array( $pSelect ) ? join( ', ', $pSelect ) : $pSelect )
			));

			return $this;
		}

		public function from( $pTable )
		{
			array_push($this->queryArray, array(
				'type' => 'FROM',
				'value' => $pTable
			));

			return $this;
		}

		public function where( $pField )
		{
			array_push($this->queryArray, array(
				'type' => 'WHERE',
				'value' => $pField
			));

			return $this;
		}

		public function _and( $pField )
		{
			array_push($this->queryArray, array(
				'type' => 'AND',
				'value' => $pField
			));

			return $this;
		}

		public function _or( $pField )
		{
			array_push($this->queryArray, array(
				'type' => 'OR',
				'value' => $pField
			));

			return $this;
		}

		public function groupBy( $pField )
		{
			array_push($this->queryArray, array(
				'type' => 'GROUP BY',
				'value' => $pField
			));

			return $this;
		}

		public function orderBy( $pField )
		{
			array_push($this->queryArray, array(
				'type' => 'ORDER BY',
				'value' => $pField
			));

			return $this;
		}

		public function limit( $pLimit )
		{
			array_push($this->queryArray, array(
				'type' => 'LIMIT',
				'value' => $pLimit
			));

			return $this;
		}

		public function parenth( $pParenth )
		{
			array_push($this->queryArray, array(
				'type' => $pParenth,
				'value' => $pParenth
			));

			return $this;
		}

		/*** FUNCIONES DE COMPARACIÓN ***/

		public function eq( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '=',
				'value' => $pValue
			));

			return $this;
		}

		public function neq( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '!=',
				'value' => $pValue
			));

			return $this;
		}

		public function in( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => 'IN',
				'value' => $pValue
			));

			return $this;
		}

		public function notin( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => 'NOT IN',
				'value' => $pValue
			));

			return $this;
		}

		public function like( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => 'LIKE',
				'value' => $pValue
			));

			return $this;
		}

		public function ilike( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => 'ILIKE',
				'value' => $pValue
			));

			return $this;
		}

		public function between( $pValue1 = null, $pValue2 = null )
		{
			array_push($this->queryArray, array(
				'type' => 'BETWEEN',
				'value' => array( $pValue1, $pValue2 )
			));

			return $this;
		}

		public function gt( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '>',
				'value' => $pValue
			));

			return $this;
		}

		public function lt( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '<',
				'value' => $pValue
			));

			return $this;
		}

		public function gte( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '>=',
				'value' => $pValue
			));

			return $this;
		}

		public function lte( $pValue = null )
		{
			array_push($this->queryArray, array(
				'type' => '<=',
				'value' => $pValue
			));

			return $this;
		}
	}
?>
