<?php
    class UsuariosController extends BaseController {
        //Método para login.
        public function logIn( $request ) {
            $params = [
                'nomUsuario' => $request->getBody('nomUsuario'),
                'contrasena' => $request->getBody('contrasena')
            ];

            $this->checkPolicies( 'usuarios', $params, 'logIn' );

            return $this->getDomain('usuarios', 'usuarios')->logIn( $params );
        }

        //Método para obtener las cuentas de los usuarios.
        public function getAccounts( $request ) {
            $params = [
                'idUsuario' => $request->getParams('idUsuario')
            ];

            return $this->getDomain('usuarios', 'usuarios')->getAccounts( $params );
        }

        public function getGodFathers( $request ) {
            $params = [
                'idUsuario' => $request->getParams('idUser')
            ];

            return $this->getDomain('usuarios', 'padrinos')->obtener( $params );
        }
    }
?>
