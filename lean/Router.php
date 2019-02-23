<?php
	/***
		Autor: Gil Verduzco
		Descripción: Clase que se encarga de registrar todas las rutas para el API REST FULL.
	***/

	require_once _AUTH;
	require_once _REQUEST;

	class Router {
		//Propiedades de la clase.
		private $lean = null;
		private $module;
		private $request;
		private $routePath;
		private $url;
		private $uri;
		private $routeFound;
		private $controller;
		private $method;
		private $methodBefore;
		private $verifyToken;
		private $options = false;
		private $cors = false;

		public function __construct($lean) {
			$this->lean = $lean;
			$this->request = new Request();
		}

		// $module SETTER/GETTER
		public function getModule() {
			return $this->module;
		}

		// $request SETTER/GETTER
		public function getRequest() {
			return $this->request;
		}

		private function setCors( $cors ) {
			if( isset( $cors['origin'] ) && isset( $cors['credentials'] ) && $cors['methods'] && $cors['headers'] ) {
				if( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
					$httpOrigin = $_SERVER['HTTP_ORIGIN'];
					$httpOriginAllowed = is_array( $cors['origin'] ) ? $cors['origin'] : [$cors['origin']];
					$httpOriginAllowedCount = sizeof( $httpOriginAllowed );

					for( $x=0; $x<$httpOriginAllowedCount; $x++ ) {
						if( $httpOriginAllowed[$x] === '*' || $httpOriginAllowed[$x] === $httpOrigin ) {
							header('Access-Control-Allow-Origin: ' . $httpOriginAllowed[$x] );
							break;
						}
					}

					header('Access-Control-Allow-Credentials: ' . $cors['credentials'] );
					header('Access-Control-Allow-Methods: ' . $cors['methods'] );
					header('Access-Control-Allow-Headers: ' . $cors['headers'] );
					header('Access-Control-Max-Age: ' . 3600 );
				}
			}
		}

		// Función para inicializar las uris.
		public function init() { 
			$this->routeFound = false;
			$uriWrong = false;
            $uriInterfere = null;
			$config = $this->lean->getConfig();
			$showDetailErrors = $config['debug'] ?? false;
			$verbIsAllowed = false;
			$response = null;
			$routesFile = _CONFIG.'/routes.config.php';
			$url = $this->request->getUri();
			$moduleArr = explode( '/', $url );
			$this->module = count( $moduleArr ) > 0 ? $moduleArr[1] : null;
			$requestVerb = strtolower( $this->request->getVerb() );

			if( !$this->module ) {
				$this->lean->setRouterError(500, 'There is not a module for this route.');
			} else {
				$routesFile = _MODULES . '/' . $this->module . '/config/routes.config.php';
			}

			//Validamos si es un options y buscamos la ruta a la cual quiere ir.
			if( $requestVerb === 'options' )  {
				$requestVerb = $this->request->getHeader('Access-Control-Request-Method');
				$this->options = true;
				return;
			}

			if( count( glob($routesFile) ) ) {
				$routes = require_once $routesFile;
				$handler = null;
				$urlWrong = false;
				$url = strtok( str_replace( $this->module . '/', '', $url ), '?' ); // Quitar nombre del módulo de URI
				$routesVerb = isset( $routes[ $requestVerb ] ) && is_array( $routes[ $requestVerb ] ) && $routes[ $requestVerb ] ? $routes[ $requestVerb ] : array();
				
				//Se recorren las rutas válidas.
				foreach( $routesVerb as $route ) {
					if( $this->routeFound )
						break;
							
					$verb = $requestVerb; 
					$uri = $route['uri'];
					$handler = $route['handler'];
					$urlArr = explode( '/', $url );
					$urlArrCount = count( $urlArr );
					$uriArr = explode( '/', $uri );
                    $uriArrCount = count( $uriArr );
					$reEx = '';
					$lastY = 0;

					//Validación de la URI.
					if( $urlArrCount === $uriArrCount ) {
						for( $y = 0; $y < $uriArrCount; $y++ ) {
							if( $uriArr[$y] !== '' ) {
								if( $reEx === '' ) {
									$reEx .= '/' . $uriArr[$y];
									
									//Las uri's no deben empezar con un valor dinámico. Ejemplo: /{idGil}/ejemplo
									if( preg_match( '/^{/', $uriArr[$y] ) && preg_match( '/}$/', $uriArr[$y] ) ) {
										$this->lean->setDevError('This resource (' . $uri . ') is invalid. The uri can not start with "/{key}".');
									}
								} else {
									if( preg_match( '/^{/', $uriArr[$y] ) && preg_match( '/}$/', $uriArr[$y] ) ) {
										$reEx .= '/(.+)';
									
										//Con esto validremos que no vengan dos variables juntas en la URI.
										//Esto significa que la URI está mal declarada.
										if( $lastY === $y - 1 ) {
											
											if( explode( '/', $url )[1] === explode( '/', $uri )[1] )
											{
												$uriWrong = true;
												$uriInterfere = $uri;
												break;
											}
										} else {
											$lastY = $y;
										}
									} else {
										$reEx .= '/' . $uriArr[$y];
									}
								}
							}
						}
						$reEx = '~^' . $reEx . '$~';

						if( preg_match( $reEx, $url ) ) {
							$uriParams = array();

							//Obtenemos los parámetros.
							for( $y=0; $y<count( $uriArr ); $y++ ) {	
								if( preg_match( '/^{/', $uriArr[$y] ) && preg_match( '/}$/', $uriArr[$y] ) )	
									$uriParams[ str_replace( '}', '', str_replace( '{', '', $uriArr[$y] ) ) ] = $urlArr[$y];
							}
							
							$this->routeFound = true;
							$this->url = $url;
							$this->uri = $uri;
							$this->cors = isset( $route['cors'] ) && $route['cors'];
							$this->controller = $handler;
							$this->method = $route['method'];
							$this->methodBefore = isset( $route['before'] ) ? $route['before'] : null;
							$this->verifyToken = isset( $route['verifyToken'] ) ? $route['verifyToken'] : true;
							$this->request->setParams( $uriParams );
							$this->request->setBaseURI( '/' . $this->module . $uri );

							break;
						}
					}
				}

				if( !$this->routeFound ) {
					if( $uriWrong ) {
						$this->lean->setRouterError(500, 'The uri "' . $uriInterfere . '" is interfering with the uri "' . $url . '". Please, solve this problem.');
					} else {
						$this->lean->setRouterError(404, 'Resource "' . $url . '" not found in module "' . $this->module . '".');
					}
				}
			} else {
				$this->lean->setRouterError(500, 'There is not a routes file for module "' . $this->module . '" .');
			}
			
			return $this;
		}

		public function run() {
			$response = [];

			if( $this->routeFound || $this->options ) {
				$corsAllow = [
					'origin' => '*',
					'credentials' => '*',
					'methods' => '*',
					'headers' => '*',
					'credentials' => true
				];
				$configFile = _CONFIG . '/modules/' . $this->module . '.config.php';
				$moduleCondig = [];
				$controller = $this->controller;
				$method = $this->method;
				$methodBefore = $this->methodBefore;

				//Agregar configuraciones del módulo.
				if( count( glob( $configFile ) ) ) {
					$moduleCondig = require_once $configFile;
					$this->lean->setConfig( array_merge( $this->lean->getConfig(), $moduleCondig ) );
				}
			
				$secretString = $this->lean->getConfig('secretString');
				$cors = $this->lean->getConfig('cors');

				if( $this->options ) {
					if( $cors ) {
						$this->setCors( $cors );
					} else {
						$this->setCors( $corsAllow );
					}

					return;
				} elseif( !$this->options && $cors ) {
					$this->setCors( $cors );
				} else {
					$this->setCors( $corsAllow );
				}
				
				$getFileClassName = function( $pController ){
					//Obtenemos el controlador y ejecutamos el método correspondiente.
					$controller = explode( ':', $pController );

					//Validamos que el controller tenga la nomenclatura correcta.
					if( !( count( $controller ) === 1 || count( $controller ) === 2 ) ) {
						$this->oLM->setDevError( 'Please check that the controller name has the correct nomenclature.'
							. ( $showDetailErrors ? ' (' . $pController . ')' : '' ) );
					}

					$fileName = strtolower( $controller[0] ) . '.controller';
					$class = ( count( $controller ) === 2 ? $controller[1] : $controller[0] );
					$className = '';
					$classNameArray = explode('-', $class);

					foreach( $classNameArray as $element ) {
						$className .= ucfirst($element);
					}
					$className .= 'Controller';

					require_once _MODULES . '/' . $this->module . '/controllers/' . $fileName . '.php';

					return array(
						'fileName' => $fileName,
						'className' => $className
					);
				};
				
				$heanlderInfo = $getFileClassName($controller);
				
				$className = $heanlderInfo['className'];
				$oController = new $className;

				//Llamar a la función definida como "before".
				if( $methodBefore ) {
					$oController->$methodBefore( $this->request );
				}

				//Verificar token de sesión
				$tokenVerify = 'invalid';

				//Verificamos que el token sea correcto.
				if( $this->verifyToken ) {
					$authorization = $this->request->getHeader('Authorization');

					if( !$authorization ) {
						$this->lean->setAuthError('Authorization token is required.');
					}

					$token = str_replace('Bearer ', '', $authorization);
					$auth = new Auth();
 					$tokenData = $auth->verify( $token, $secretString );
					$this->request->setTokenData($tokenData);
					$tokenVerify = 'valid';
				} else {
					$tokenVerify = 'valid';
				}

				if( $tokenVerify === 'valid' ) {
					$response = $oController->$method($this->request);
				} else {
					$this->lean->setAuthError('Invalid session.');
				}
			}

			return $response;
		}

	}
?>
