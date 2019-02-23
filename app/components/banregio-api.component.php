<?php
    require_once _APP . '/components/curl.component.php';

    class BanregioApiComponent extends BaseController{
      private static $headerString = 'Authorization: Bearer {token}';

        private static function generarToken( $params ) {
            $body = [
                'grant_type' => $params['grantType'],
                'code' => $params['code'],
                'client_id' => $params['clientID'],
                'client_secret' => $params['clientSecret'],
                'redirect_uri' =>  $params['redirectUri']
            ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/oauth/token/'
            ]);
            return json_decode($oCurl->post( http_build_query($body) ));
        }

        public static function consultaCuentas( $params ) {
            $lean = Lean::getInstance();
            $banregioConfig = $lean->getConfig('banregioData');
            $uri =  $banregioConfig['banregioBaseUrl'].'/v1/accounts';
            if (isset($params['idCuenta'])) {
              $uri.=$params['idCuenta'];
            }
            // var_dump(self::$headerString);  die();
            // $header = str_replace( '{token}', $params['token'], $this->$headerString )
            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/v1/accounts',
                'headers' => [
                    str_replace( '{token}', $params['token'], self::$headerString )
                ]
            ]);
            var_dump($oCurl->get());  die();
            // $response = json_decode();
            if ($response->error) {
                $tokenData = $this->refrescarToken();
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                $this->consultaTransacciones($retryParams);
            }
            return $response;
        }

        public static function consultaTransacciones( $params ) {

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/v1/accounts/'.$params['idCuenta'].'/transactions',
                'headers' => [
                    str_replace( '{token}', $params['token'], $headerString )
                ]
            ]);
            $response = json_decode($oCurl->post( http_build_query($body) ));
            if ($response->error) {
                $tokenData = $this->refrescarToken();
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                $this->consultaTransacciones($retryParams);
            }
            return $response;
        }

        private static function refrescarToken( $params ) {
            $lean = Lean::getInstance();
            $banregioConfig = $lean->getConfig('banregioData');
            $body = [
                'grant_type' => $banregioConfig['authorizationCode'],
                'code' => $request->getQuery('code'),
                'client_id' => $banregioConfig['clientID'],
                'client_secret' => $banregioConfig['clientSecret'],
                'redirect_uri' =>  $this->getConfig('urlBase').$banregioConfig['redirectUri']
            ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/oauth/token/'
            ]);
            $tokenData = json_decode($oCurl->post( http_build_query($body) ));
            $paramsToken = [
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token,
            ];
            $this->getModel('banregio', 'banregio-token')->guardar( $params );
            return $paramsToken;
        }

    }
?>
