<?php
    class FinLabController extends BaseController {
        //Método para obtener el balance por número de cuenta.
        public function getBalanceByAccount( $request ) {
            $params = [
                'accountNumber' => $request->getParams('accountNumber')
            ];

            $cuenta = $this->getDomain('api', 'fin-lab')->getBalanceByAccount( $params )[0];
            $cuenta['numCuenta'] = $params['accountNumber'];
            $cuenta['saldo'] = $cuenta['balance'];

            return $cuenta;
        }

        //Método para obtener el los movimientos..
        public function getTransactions( $request ) {
            $params = [
                'accountNumber' => $request->getParams('accountNumber')
            ];

            return $this->getDomain('api', 'fin-lab')->getTransactions( $params );
        }

        //Método para obtener el los movimientos..
        public function createAccount( $request ) {
            $params = [
                'nombre' => $request->getBody('nombre'),
                'apellidoPaterno' => $request->getBody('apellidoPaterno'),
                'apellidoMaterno' => $request->getBody('apellidoMaterno'),
                'fechaNacimiento' => $request->getBody('fechaNacimiento'),
                'ine' => $request->getBody('ine'),
                'cardIdentification' => $request->getBody('cardIdentification'),
                'celular' => $request->getBody('celular')
            ];

            return $this->getDomain('api', 'fin-lab')->createAccount( $params );
        }
    }
?>