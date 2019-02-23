<?php
    /***
        Autor: Gil Verduzco
        Descripción: Clase para generar y validar el token basado en JWT.
    ***/
    // require_once( _IROOT.'/vendor/Firebase/JWT/JWT' );

    use \Firebase\JWT\JWT;
    use \Firebase\JWT\ExpiredException;

    class Auth
    {
        private $app;
        private static $secret = 'j¡w@t&l$e#a=n';

        public function __construct()
        {
            $this->app = Lean::getInstance();
        }

        //Método para crear el token basado en JWT.
        public static function signIn( $pData, $pExp = 3600, $pSecret = null )
        {
            return JWT::encode(array(
                'iat' => time(),
                'nbf' => time(),
                'exp' => time() + $pExp * 1000,
                'data' => $pData
            ), is_null( $pSecret ) ? self::$secret : $pSecret );
        }

        //Método para verificar el token.
        public function verify( $pToken, $pSecret )
        {
            $expiredMessage = 'Sessión has expired, sign in and try again.';
            $data = null;

            try {
                $data = JWT::decode($pToken, $pSecret, array('HS256'));
                $tokenVerify = 'valid';
            } catch (Exception $e) {
                $this->app->setAuthError($expiredMessage);
            }

            return (array) $data->data;
        }
    }
?>
