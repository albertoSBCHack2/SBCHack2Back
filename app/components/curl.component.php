<?php
    class CurlComponent {
        private $oCurl;
        private $config;

        //Construct
        public function __construct( $params ) {
            $this->config = $params;
        }

        //Método para iniciar el curl.
        private function init() {
            $this->oCurl = curl_init( $this->config['url'] );
            curl_setopt( $this->oCurl, CURLOPT_RETURNTRANSFER, true );

            //HEADERS
            if( isset( $this->config['headers'] ) && is_array( $this->config['headers'] ) ) {
                $headers = array();

                foreach( $this->config['headers'] as $key => $value ) {
                    $headers[ $key ] = $value;
                }

                curl_setopt( $this->oCurl, CURLOPT_HTTPHEADER, $headers );
            }

            if( isset( $this->config['sslVerifyPeer'] ) ) {
                curl_setopt( $this->oCurl, CURLOPT_SSL_VERIFYPEER, $this->config['sslVerifyPeer'] );
            }

            if( isset( $this->config['sslVerifyHost'] ) ) {
                curl_setopt( $this->oCurl, CURLOPT_SSL_VERIFYHOST, $this->config['sslVerifyHost'] );
            }

            if( isset( $this->config['header'] ) ) {
                curl_setopt( $this->oCurl, CURLOPT_HEADER, $this->config['header'] );
            }

            if( isset( $this->config['followLocation'] ) ) {
                curl_setopt( $this->oCurl, CURLOPT_FOLLOWLOCATION, $this->config['followLocation'] );
            }
        }

        //GET
        public function get() {
            //Seteamos el curl.
            $this->init();
            $response = curl_exec( $this->oCurl );
            curl_close( $this->oCurl );

            return $response;
        }

        //POST
        public function post( $params = null ) {
            //Seteamos el curl.
            $this->init();
            curl_setopt( $this->oCurl, CURLOPT_POST, true );

            if( $params ) {
                curl_setopt( $this->oCurl, CURLOPT_POSTFIELDS, $params );
            }

            $response = curl_exec( $this->oCurl );
            curl_close( $this->oCurl );
            return $response;
        }
    }
?>
