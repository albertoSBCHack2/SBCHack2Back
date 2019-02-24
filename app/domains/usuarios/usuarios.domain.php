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
            $fecCaducidad = date( 'Y-m-d', strtotime( $fecCaducidad. ' + ' . $params['diasDelReto'] . ' days' ) );

            //Validamos que no exista un reto pendiente.
            $retoPendiente = $this->getModel('usuarios', 'retos')->obtenerPorPadrino([
                'idUsuarioPadrino' => $params['idUsuarioPadrino'],
                'vigente' => true
            ]);

            if( $retoPendiente ) {
                $this->setError('Ya existe un reto pendiente.');
            }

            //Consultamos los ahijados del padrino.
            $ahijados = $this->getModel('usuarios', 'padrinos-ahijados')->obtenerAhijados([
                'idUsuario' => $params['idUsuarioPadrino']
            ]);

            //Calculamos el total a transferir.
            $totalGanaAhijado = $params['monto'] * 1 + $params['bono'];
            $totalRestarPadrino = $params['monto'] * count( $ahijados ) + $params['bono'];

            //Guardamos el reto.
            $idReto = $this->getModel('usuarios', 'retos')->agregar([
                'id_usuario_padrino_reta' => $params['idUsuarioPadrino'],
                'id_cuenta' => $params['idCuenta'],
                'id_banco' => $params['idBanco'],
                'monto' => $params['monto'],
                'bono' => $params['bono'],
                'saldo_en_reto' => $totalGanaAhijado,
                'restar_padrino' => $totalRestarPadrino,
                'vigente' => true,
                'fec_caducidad' => $fecCaducidad,
                'fec_registro' => date('Y-m-d H:i:s')
            ]);

            //Avisamos a los ahijados por medio de una push notification.
            foreach( $ahijados as $ahijado ) {
                $this->getModel('usuarios', 'push-notification')->agregar([
                    'id_usuario' => $ahijado['idUsuario'],
                    'mensaje' => 'Tu padrino ' . $params['nombrePadrino'] . ' te ha asignado un nuevo reto.'
                ]);
            }

            return [
                'idReto' => $idReto
            ];
        }

        //Método para hacer transferencias.
        public function transfer( $params ) {
            //Primeramente hacemos la transferencia.
            $transfer1 = $this->getDomain('api', 'hsbc')->transfer( $params );

            //Validamos si es ahijado y si tiene un reto vigente.
            if( $params['idRol'] == 2 && $params['transactionAmount'] > 0 ) {
                $reto = $this->getModel('usuarios', 'retos')->obtenerPorAhijado([
                    'idUsuario' => $params['idUsuario'],
                    'vigente' => true
                ])[0] ?? null;

                if( $reto && $params['transactionAmount'] >= $reto['monto'] ) {
                    //Validamos si cumple con el reto.
                    if( $reto['vigente'] ) { //Se ha cumplido el reto. 
                        //Consultamos la cuenta del padrino.
                        $cuentaPadrino = $this->getModel('usuarios', 'usuarios')->getAccounts([
                            'idUsuario' => $reto['idUsuarioPadrinoReta'],
                            'idCuenta' => $reto['idCuenta']
                        ])[0];

                        if( $cuentaPadrino['idBanco'] == 1 ) {
                            //Transferimos al ahijado.
                            $transfer2 = $this->getDomain('api', 'hsbc')->transfer([
                                'sourceAccount' => $cuentaPadrino['numCuenta'],
                                'destinationAccount' => $params['destinationAccount'],
                                'transactionAmount' => $reto['saldoEnReto'],
                                'description' => 'Reto ganado',
                            ]);

                            //Marcamos que el reto ya fue ganado.
                            $this->getModel('usuarios', 'retos')->actualizar([
                                'vigente' => false,   
                            ], [
                                'id_usuario_padrino_reta' => $reto['idUsuarioPadrinoReta'],
                                'id_reto' => $reto['idReto']
                            ]);

                            //Enviamos push al padrino.
                            $this->getModel('usuarios', 'push-notification')->agregar([
                                'id_usuario' => $reto['idUsuarioPadrinoReta'],
                                'mensaje' => 'Se te ha descontado el saldo pendiente del reto. Uno de tus ahijados ha ganado.'
                            ]);

                            //Enviamos push al ahijado ganador.
                            $this->getModel('usuarios', 'push-notification')->agregar([
                                'id_usuario' => $params['idUsuario'],
                                'mensaje' => '¡Felicidades! Has ganado el reto.'
                            ]);
                        }
                    }
                }
            }
        }

        //Método para obtener las push notifications por usuario.
        public function getPushNotifications( $params ) {
            $pushNotification = $this->getModel('usuarios', 'push-notification')->obtener( $params );

            if( count( $pushNotification ) > 0 ) {
                $pushNotification = $pushNotification[ count( $pushNotification ) - 1 ];
            } else {
                $pushNotification = null;
            }
            
            //La inactivamos.
            // $this->getModel('usuarios', 'push-notification')->actualizar([
            //     'activa' => false
            // ], [
            //     'id_push_notification' => $pushNotification['idPushNotification']
            // ]);

            return $pushNotification;
        }
    }
?>