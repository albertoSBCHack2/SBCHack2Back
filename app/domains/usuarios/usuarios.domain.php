<?php
    class UsuariosDomain extends BaseDomain {
        //Método para login.
        public function logIn( $params ) {
            //Consultamos si el usuario existe o es válido.
            $usuario = $this->getModel('usuarios', 'usuarios')->obtener( $params );

            if( !$usuario ) {
                $this->setError('Usuario o contraseña inválidos.');
            }

            return $usuario[0];
        }
    }
?>