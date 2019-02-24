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
        
            $cuenta = $this->getDomain('api', 'hsbc')->getBalanceByAccount( $params );
            $cuenta['numCuenta'] = $params['accountNumber'];
            $cuenta['saldo'] = $cuenta['available'];

            return $cuenta;
         }

         //Método para obtener los movimientos
         public function getCheckingAccountStatement( $request ) {
            $params = [
                'accountNumber' => $request->getQuery('accountNumber'),
                'movementsNumber' => $request->getQuery('movementsNumber')
            ];
        
            return $this->getDomain('api', 'hsbc')->getCheckingAccountStatement( $params );
         }

         //Método para hacer transferencias.
         public function transfer( $request ) {
            $params = [
                'sourceAccount' => $request->getBody('sourceAccount'),
                'destinationAccount' => $request->getBody('destinationAccount'),
                'transactionAmount' => $request->getBody('transactionAmount'),
                'description' => $request->getBody('description')
            ];
 
            return $this->getDomain('api', 'hsbc')->transfer( $params );
         }
    }
?>