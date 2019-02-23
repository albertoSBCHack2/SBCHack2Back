<?php
    class HSBCController extends BaseController {
         //Método para obtener el profile del cliente.
         public function getProfileClient( $request ) {
             $params = [
                'clientNumber' => $this->getParam('clientNumber')
             ];

            $oCurl = new CurlComponent([
                'url' => $banregioConfig['banregioBaseUrl'].'/oauth/token/'
            ]);
            $response = $oCurl->post( http_build_query($body) );
         }
    }
?>