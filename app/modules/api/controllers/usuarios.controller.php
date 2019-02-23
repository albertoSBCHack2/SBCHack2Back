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
                'idUsuario' => $request->getTokenData('idUsuario'),
                'idBanco' => $request->getQuery('idBanco')
            ];

            return $this->getDomain('usuarios', 'usuarios')->getAccounts( $params );
        }
        //Método para obtener los padrinos del usuario enviado
        public function getGodFathers( $request ) {
            $params = [
                'idUsuario' => $request->getParams('idUser')
            ];
            return $this->getDomain('usuarios', 'padrinos')->obtener( $params );
        }

        //Métodos para agregar ahijados.
        public function agregarAhijado( $request ) {
            $params = [
                'idUsuarioPadrino' => $request->getBody('idUsuarioPadrino'),
                'idUsuarioAhijado' => $request->getBody('idUsuarioAhijado')
            ];

            $this->getDomain('usuarios', 'ahijados')->agregar( $params );
        }

        //Métodos para agregar padrinos.
        public function agregarPadrino( $request ) {
            $params = [
                'idUsuarioPadrino' => $request->getBody('idUsuarioPadrino'),
                'idUsuarioAhijado' => $request->getBody('idUsuarioAhijado')
            ];

            $this->getDomain('usuarios', 'padrinos')->agregar( $params );
        }
    }
?>
