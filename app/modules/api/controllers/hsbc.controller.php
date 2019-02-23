<?php
    class HsbcController extends BaseController {       
         //Método para obtener el profile del cliente.
         public function getProfileClient( $request ) {
            $params = [
                'clientNumber' => $request->getParams('clientNumber')
            ];
        
            return $this->getDomain('api', 'hsbc')->getProfileClient( $params );
         }
    }
?>