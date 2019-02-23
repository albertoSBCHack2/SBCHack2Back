<?php
    require_once _APP . '/components/banregio-api.component.php';

    class BanregioDomain extends BaseDomain {
        public function guardarToken( $params ) {
          $this->getModel('banregio', 'banregio-token')->guardar( $params );
        }

        public function obtenerToken( $params = [] ) {
          return $this->getModel('banregio', 'banregio-token')->obtener($params)[0] ?? null;
        }

        public static function consultaTransacciones( $params ) {
          $oCurl = new CurlComponent([
              'url' => $this->getConfig('banregioData')['banregioBaseUrl'] . '/v1/accounts/'.$params['idCuenta'].'/transactions',
              'headers' => [
                  str_replace( '{token}', $params['token'], $headerString )
              ]
          ]);
          $response = json_decode($oCurl->post( http_build_query($body) ));

          if ($response->error) {
              $tokenData = BanregioApiComponent::refrescarToken( $params );
              $retryParams = [
                  'idCuenta' => $params['idCuenta'],
                  'token' => $tokenData['access_token'],
              ];

              $this->consultaTransacciones($retryParams);
          }
          
          return $response;
      }
    }
?>
