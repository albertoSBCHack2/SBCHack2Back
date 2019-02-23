<?php
    class UsuariosDomain extends BaseDomain {
        //Método para login.
        public function logIn( $params ) {
            //Consultamos si el usuario existe o es válido.
            $usuario = $this->getModel('usuarios', 'usuarios')->getUser( $params )[0] ?? null;

            if( !$usuario ) {
                $this->setError('Usuario o contraseña inválidos.');
            }

            //Generamos el token.
            $token = Auth::signIn([
                'idUsuario' => $usuario['id_usuario'],
                'idRol' => $usuario['id_rol'],
                'nombre' => $usuario['nombre']
            ], 86400, $this->getConfig('secretString'));

            return $token;
        }

        //Método para obtener las cuentas de los usuarios.
        public function getAccounts( $params ) {
            return $this->getModel('usuarios', 'usuarios')->getAccounts( $params )[0] ?? null;
        }
    }
?>