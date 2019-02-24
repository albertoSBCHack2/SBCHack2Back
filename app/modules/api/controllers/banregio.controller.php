<?php
  require_once _APP . '/components/banregio-api.component.php';

    class BanregioController extends BaseController {
        //Método para obtener el auth token
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
            return $this->getDomain('api', 'banregio')->guardarToken( $paramsToken );
        }
        //Método para obtener las cuentas
        public function obtenerCuentas($request)
        {
            $token =  $this->getDomain('api', 'banregio')->obtenerToken( [] );
            if (!$token) {
              $this->setError('No se encontró token');
            }
            $params = [
                'token' =>$token['access_token'],
                'refreshToken' =>$token['refresh_token'],
                'idCuenta' =>$request->getParams('idAccount') ?? null,
            ];
            return $this->getDomain('api', 'banregio')->consultaCuentas( $params );
        }
        //Método para obtener las transacciones
        public function consultaTransacciones($request)
        {
            $token =  $this->getDomain('api', 'banregio')->obtenerToken( [] );
            if (!$token) {
              $this->setError('No se encontró token');
            }
            $params = [
                'token' =>$token['access_token'],
                'refreshToken' =>$token['refresh_token'],
                'idCuenta' =>$request->getParams('idAccount') ?? null,
            ];
            $cuenta = $this->getDomain('api', 'banregio')->consultaTransacciones( $params )[0];
            $cuenta['numCuenta'] = $params['idCuenta'];
            $cuenta['saldo'] = $cuenta['amount'];
            return $cuenta;
        }

    }
 ?>
