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

            $header = [
              'Authorization: Bearer '.$params['token']
            ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/v1/accounts',
                'headers' => [
                  str_replace( '{token}', $params['token'], self::$headerString )
                ],
                'followLocation' => 1
            ]);
            $response = json_decode($oCurl->get());
            if ($response->error) {
                $tokenData = self::refrescarToken($params);
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                self::consultaTransacciones($retryParams);
            }
            return $response;
        }

        public static function consultaTransacciones( $params ) {
            $lean = Lean::getInstance();
            $banregioConfig = $lean->getConfig('banregioData');

            $header = [
              'Authorization: Bearer '.$params['token']
            ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/v1/accounts/'.$params['idCuenta'].'/transactions',
                'headers' => [
                  str_replace( '{token}', $params['token'], self::$headerString )
                ],
                'followLocation' => 1
            ]);
            $response = json_decode($oCurl->get());
            if ($response->error) {
                $tokenData = self::refrescarToken($params);
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                self::consultaTransacciones($retryParams);
            }
            return $response;

        }

        private static function refrescarToken($params) {
            $lean = Lean::getInstance();
            $banregioConfig = $lean->getConfig('banregioData');
            $body = [
                'grant_type' => $banregioConfig['refreshToken'],
                'refresh_token' => $params['refreshToken'],
                'client_id' => $banregioConfig['clientID'],
                'client_secret' => $banregioConfig['clientSecret'],
                'redirect_uri' =>  $lean->getConfig('urlBase').$banregioConfig['redirectUri']
            ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/oauth/token/'
            ]);
            $tokenData = json_decode($oCurl->post( http_build_query($body) ));
            var_dump($body);  die();
            $paramsToken = [
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token,
            ];
            $lean->getModel('banregio', 'banregio-token')->guardar( $paramsToken );
            return $paramsToken;
        }

    }
?>
