<?php
    require_once _APP . '/components/banregio-api.component.php';

    class BanregioDomain extends BaseDomain {
        public function guardarToken( $params ) {
            $this->getModel('banregio', 'banregio-token')->guardar( $params );
        }

        public function obtenerToken( $params = [] ) {
            return $this->getModel('banregio', 'banregio-token')->obtener($params)[0] ?? null;
        }

        public static function consultaTransacciones( $params )
        {
            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/v1/accounts/'.$params['idCuenta'].'/transactions',
                'headers' => [
                  'Authorization: Bearer ' . $params['token']
                ],
                'followLocation' => 1
            ]);
            $response = json_decode($oCurl->post( http_build_query($body) ));

            if ($response->error) {
                $tokenData = $this->refrescarToken($params);
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                $this->consultaTransacciones($retryParams);
            }

            return $response;
        }
        public static function consultaCuentas( $params )
        {
            $uri =  $banregioConfig['banregioBaseUrl'].'/v1/accounts';
            if (isset($params['idCuenta'])) {
                $uri.=$params['idCuenta'];
            }
            $oCurl = new CurlComponent([
                'url' => $uri,
                'headers' => [
                  'Authorization: Bearer '.$params['token']
                ],
                'followLocation' => 1
            ]);
            $response = json_decode($oCurl->post( http_build_query($body) ));

            if ($response->error) {
                $tokenData = $this->refrescarToken($params);
                $retryParams = [
                    'idCuenta' => $params['idCuenta'],
                    'token' => $tokenData['access_token'],
                ];
                $this->consultaCuentas($retryParams);
            }

            return $response;
        }
    }
?>
