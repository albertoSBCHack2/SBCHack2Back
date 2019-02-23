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
    }
?>