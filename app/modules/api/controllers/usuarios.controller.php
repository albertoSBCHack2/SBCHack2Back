<?php
    class UsuariosController extends BaseController {
        //Método para login.
        public function logIn( $request ) {
            $params = [
                'nom_usuario' => $request->getBody('nom_usuario'),
                'contrasena' => $request->getBody('contrasena')
            ];

            $this->checkPolicies( 'usuarios', $params, 'logIn' );

            return $this->getDomain('usuarios', 'usuarios')->logIn( $params );
        }
    }
?>