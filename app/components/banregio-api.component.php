<?php
    require_once _APP . '/components/curl.component.php';

    class BanregioApiComponent extends BaseController{
      private static $headerString = 'Authorization: Bearer {token}';

        public static function generarToken( $params ) {
            $lean = Lean::getInstance();
            $banregioConfig = $lean->getConfig('banregioData');
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

        public static function refrescarToken($params) {
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
            $paramsToken = [
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token,
            ];
            $lean->getModel('banregio', 'banregio-token')->guardar( $paramsToken );
            return $paramsToken;
        }

    }
?>
