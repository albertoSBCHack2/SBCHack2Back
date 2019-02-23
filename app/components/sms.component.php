<?php
    require_once _APP . '/components/curl.component.php';

    class SMSComponent {
        //Método para enviar sms default.
        public static function sendDefault( $params )
        {
            $lean = Lean::getInstance();

            //Llamamos al método default.
            $method = $lean->getConfig('sms')['default'];
            return self::$method( $params );
        }

        //Método para iniciar el curl.
        private static function sendCalixta( $params ) {
            $lean = Lean::getInstance();
            $datosConfig = $lean->getConfig('sms');
            $paramsCurl = [
                'cte' => $datosConfig['apis']['calixta']['cte'],
                'encpwd' => $datosConfig['apis']['calixta']['encpwd'],
                'email' => $datosConfig['apis']['calixta']['email'],
                'mtipo' => $datosConfig['apis']['calixta']['mtipo'],
                'idivr' => $datosConfig['apis']['calixta']['idivr'],
                'auxiliar' => $datosConfig['apis']['calixta']['auxiliar'],
                'msg' => $params['message'],
                'numtel' => $lean->getEnv() === 'production' ? $params['cellphone'] : $datosConfig['adminCelular'],
            ];

            $oCurl = new CurlComponent([
                'url' => $datosConfig['apis']['calixta']['url']
            ]);
            
            return $oCurl->post( http_build_query($paramsCurl) );
        }
    }
?>
