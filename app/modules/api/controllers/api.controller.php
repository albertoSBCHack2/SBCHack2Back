<?php
  require_once _APP . '/components/curl.component.php';

    class ApiController extends BaseController {
        public function obtenerAuthToken($request)
        {
            $banregioConfig = $this->getConfig('banregioData');
            $body = [
                'grant_type' => $banregioConfig['authorizationCode'],
                'code' => $request->getQuery('code'),
                'client_id' => $banregioConfig['clientID'],
                'client_secret' => $banregioConfig['clientSecret'],
                'redirect_uri' =>  $this->getConfig('urlBase').'/api/banregio-callback-auth'
            ];
            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/oauth/token/'
            ]);
            $response = $oCurl->post( http_build_query($body) );

        }

    }
 ?>
