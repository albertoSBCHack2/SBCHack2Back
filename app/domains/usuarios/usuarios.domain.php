<?php
    class UsuariosDomain extends BaseDomain {
        //Método para login.
        public function logIn( $params ) {
            //Consultamos si el usuario existe o es válido.
            $usuario = $this->getModel('usuarios', 'usuarios')->obtener( $params )[0] ?? null;

            if( !$usuario ) {
                $this->setError('Usuario o contraseña inválidos.');
            }

            //Generamos el token.
            $token = Auth::signIn([
                'idUsuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre']
            ], 86400, $this->getConfig('secretString'));

            return $token;
        }
    }
?>