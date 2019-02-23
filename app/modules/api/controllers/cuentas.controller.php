<?php
    class CuentasController extends BaseController {
        //Método para asociar cuentas.
        public function agregar( $request ) {
            $params = [
                'idUsuario' => $request->getTokenData('idUsuario'),
                'idBanco' => $request->getBody('idBanco'),
                'numCuenta' => $request->getBody('numCuenta')
            ];

            $this->checkPolicies( 'cuentas', $params, 'asociarCuenta' );

            return $this->getDomain('usuarios', 'cuentas')->agregar( $params );
        }
    }
?>