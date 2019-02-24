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
            return $this->getModel('usuarios', 'usuarios')->getAccounts( $params );
        }

        //Método para agergar retos.
        public function agregarReto( $params ) {
            //Obtenemos la fecha de caducidad del reto.
            $fecCaducidad = date('Y-m-d H:i:s');
            $fecCaducidad = date( 'Y-m-d', strtotime( $fecCaducidad. ' + ' . $diasDelReto . ' days' ) );

            //Validamos que no exista un reto pendiente.
            $retoPendiente = $this->getModel('usaurios', 'retos')->obtener([
                'idUsuarioPadrino' => $params['idUsuarioPadrino'],
                'vigente' => true
            ]);

            if( $retoPendiente ) {
                $this->setError('Ya existe un reto pendiente.');
            }

            //Guardamos el reto.
            $idReto = $this->getModel('usaurios', 'retos')->agregar([
                'id_usuario_padrino_reta' => $params['idUsuarioPadrino'],
                'monto' => $params['monto'],
                'vigente' => true,
                'fec_caducidad' => $fecCaducidad,
                'fec_registro' => date('Y-m-d H:i:s')
            ]);

            //Avisamos al ahijado por medio de una push notification.

            return [
                'idReto' => $idReto
            ];
        }
    }
?>