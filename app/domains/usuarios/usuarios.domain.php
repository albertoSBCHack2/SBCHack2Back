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

            //Guardamos el reto.
            $idReto = $this->getModel('usuarios', 'retos')->agregar([
                'id_usuario_padrino_reta' => $params['idUsuarioPadrino'],
                'id_cuenta' => $params['idCuenta'],
                'monto' => $params['monto'],
                'bono' => $params['bono'],
                'vigente' => true,
                'fec_caducidad' => $fecCaducidad,
                'fec_registro' => date('Y-m-d H:i:s')
            ]);

            //Avisamos al ahijado por medio de una push notification.

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

                if( $reto ) {
                    //Quitamos las cuentas de ahorro.
                    $this->getModel('usuarios', 'cuentas')->actualizar([
                        'es_ahorro' => false
                    ], [
                        'id_usuario' => $params['idUsuario'],
                        'es_ahorro' => true
                    ]);

                    //Marcamos que esta cuenta es la de ahorro.
                    $this->getModel('usuarios', 'cuentas')->actualizar([
                        'es_ahorro' => true
                    ], [
                        'num_cuenta' => $params['transactionAmount']
                    ]);

                    //Validamos si cumple con el reto.
                    if( $reto['vigente'] ) { //Se ha cumplido el reto. 
                        //Hacemos la transferencia al ahijado.
                        if( $params['idBanco'] == 1 ) {
                            //Consultamos la información de esta cuenta.
                            $cuentaAhijado = $this->getModel('usuarios', 'usuarios')->getAccounts([
                                'numCuenta' => $params['transactionAmount']
                            ]);

                            //Consultamos los ahijados del padrino.
                            $ahijados = $this->getModel('usuarios', 'padrinos-ahijados')->obtenerAhijados([
                                'idUsuario' => $reto['idUsuarioPadrinoReta']
                            ]);

                            //Consultamos la cuenta del padrino.
                            $cuentaPadrino = $this->getModel('usuarios', 'usuarios')->getAccounts([
                                'idUsuario' => $reto['idUsuarioPadrinoReta'],
                                'idCuenta' => $reto['idCuenta']
                            ])[0];

                            //Calculamos el total a transferir.
                            $totalTransferir = $reto['monto'] * count( $ahijados ) + $reto['bono'];


                            $transfer2 = $this->getDomain('api', 'hsbc')->transfer([
                                'sourceAccount' => $cuentaPadrino['numCuenta'],
                                'destinationAccount' => $params['transactionAmount'],
                                'transactionAmount' => $totalTransferir,
                                'description' => 'Ganaste el reto',
                            ]);
                        }
                    }
                }
            }
        }
    }
?>