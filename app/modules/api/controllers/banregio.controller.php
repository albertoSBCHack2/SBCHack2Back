<?php
  require_once _APP . '/components/banregio-api.component.php';

    class BanregioController extends BaseController {
        public function obtenerAuthToken($request)
        {
            $banregioConfig = $this->getConfig('banregioData');
            $body = [
                'grantType' => $banregioConfig['authorizationCode'],
                'code' => $request->getQuery('code'),
                'clientID' => $banregioConfig['clientID'],
                'clientSecret' => $banregioConfig['clientSecret'],
                'redirectUri' =>  $this->getConfig('urlBase').$banregioConfig['redirectUri']
            ];
            $tokenData = BanregioApiComponent::generarToken($body);
            $paramsToken = [
                'access_token' => $tokenData->access_token,
                'refresh_token' => $tokenData->refresh_token,
            ];
            return $this->getDomain('banregio', 'banregio')->guardarToken( $params );
        }
        public function obtenerCuentas($request)
        {
            $token =  $this->getDomain('api', 'banregio')->obtenerToken( [] );
            if (!$token) {
              $this->setError('No se encontró token');
            }
            $params = [
                'token' =>$token['access_token'],
                'refreshToken' =>$token['refresh_token']
            ];
            return BanregioApiComponent::consultaCuentas($params);
        }

    }
 ?>
