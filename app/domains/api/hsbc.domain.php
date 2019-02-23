<?php
    require _APP . '/components/curl.component.php';

    class HsbcDomain extends BaseDomain {
        //Método para obtener el profile del cliente.
        public function getProfileClient( $params ) {
            $oCurl = new CurlComponent([
                'url' => 'https://w799f0c9c3.execute-api.us-east-1.amazonaws.com/dev/v1/sandbox/clients/' . $params['clientNumber'] . '/profile',
                'headers' => array(
                    'X-User: TEAM3',
                    'X-Client: 2c949686cdff4ffd82d0d49eb4d3a64c',
                    'X-Password: 36B9527021234dB590B7875e31b68A7D',
                    'x-api-key: mpkm16Rpz6BBXH7CsX6T4blTtBz8P1A8fFxPkNb4'
                )
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );

            if( !isset( $responseJSON['clientProfile'] ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['clientProfile'];
        }
    }
?>