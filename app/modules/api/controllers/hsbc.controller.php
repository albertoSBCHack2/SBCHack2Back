<?php
    class HsbcController extends BaseController {       
         //Método para obtener el profile del cliente por número de cliente.
         public function getProfileClientByClient( $request ) {
            $params = [
                'clientNumber' => $request->getParams('clientNumber')
            ];
        
            return $this->getDomain('api', 'hsbc')->getProfileClientByClient( $params );
         }

         //Método para obtener el profile del cliente por número de cuenta.
         public function getProfileClientByAccount( $request ) {
            $params = [
                'accountNumber' => $request->getQuery('accountNumber')
            ];
        
            return $this->getDomain('api', 'hsbc')->getProfileClientByAccount( $params );
         }

         //Método para obtener el balance por número de cuenta.
         public function getBalanceByAccount( $request ) {
            $params = [
                'accountNumber' => $request->getQuery('accountNumber')
            ];
        
            return $this->getDomain('api', 'hsbc')->getBalanceByAccount( $params );
         }
    }
?>