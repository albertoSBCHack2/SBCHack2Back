<?php
    class CuentasDomain extends BaseDomain {
        //Método para asociar cuentas.
        public function agregar( $params ) {
            $bancoDomain = null;
            $paramsBanco = [
                'accountNumber' => $params['numCuenta']
            ];
            $saldo = 0;

            //Validamos si la cuenta existe en el banco.
            if( $params['idBanco'] == 1 ) {
                $bancoDomain = 'hsbc';
            } else if( $params['idBanco'] == 2 ) {
                $bancoDomain = 'fin-lab';
            } else if( $params['idBanco'] == 3 ) {
                $bancoDomain = 'banregio';
            }

            $cuenta = $this->getDomain('api', $bancoDomain)->getBalanceByAccount( $paramsBanco );

            if( $params['idBanco'] == 1 ) {
                $saldo = $cuenta['available'];
            } else if( $params['idBanco'] == 2 ) {
                $saldo = $cuenta[0]['balance'];
            } else if( $params['idBanco'] == 3 ) {
                $saldo = $cuenta['available'];
            }


            //La cuenta no se debe repetir.
            $this->setUniqueCMsg('uq_cuentas', 'Esta cuenta ya ha sido asociada.');
            
            //Guardamos.
            $idCuenta = $this->getModel('usuarios', 'cuentas')->agregar([
                'id_usuario' => $params['idUsuario'],
                'id_banco' => $params['idBanco'],
                'num_cuenta' => $params['numCuenta']
            ]);

            return [
                'idCuenta' => $idCuenta,
                'saldo' => $saldo
            ];
        }
    }
?>