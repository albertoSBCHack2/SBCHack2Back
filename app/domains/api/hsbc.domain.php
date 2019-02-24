<?php
    require _APP . '/components/curl.component.php';

    class HsbcDomain extends BaseDomain {
        private $headers = [
            'Content-Type: application/json',
            'X-User: TEAM3',
            'X-Client: 2c949686cdff4ffd82d0d49eb4d3a64c',
            'X-Password: 36B9527021234dB590B7875e31b68A7D',
            'x-api-key: mpkm16Rpz6BBXH7CsX6T4blTtBz8P1A8fFxPkNb4'
        ];
        private $baseURL = 'https://w799f0c9c3.execute-api.us-east-1.amazonaws.com/dev';

        //Método para obtener el profile del cliente por número de cliente.
        public function getProfileClientByClient( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v1/sandbox/clients/' . $params['clientNumber'] . '/profile',
                'headers' => $this->headers
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );

            if( !isset( $responseJSON['clientProfile'] ) ) {
                $this->setError('Cliente no existe.');
            }

            return $responseJSON['clientProfile'];
        }

        //Método para obtener el profile del cliente por número de cuenta.
        public function getProfileClientByAccount( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v1/sandbox/checking-accounts/profile?accountNumber=' . $params['accountNumber'],
                'headers' => $this->headers
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );

            if( !isset( $responseJSON['accountProfile'] ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['accountProfile'];
        }

        //Método para obtener el balance por número de cuenta.
        public function getBalanceByAccount( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v1/sandbox/checking-accounts/balance?accountNumber=' . $params['accountNumber'],
                'headers' => $this->headers
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );

            if( !isset( $responseJSON['accountBalance'] ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['accountBalance'];
        }

        //Método para obtener los movimientos.
        public function getCheckingAccountStatement( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v1/sandbox/checking-accounts/account-statement' .
                    '?accountNumber=' . $params['accountNumber'] .
                    '&movementsNumber=' . $params['movementsNumber'],
                'headers' => $this->headers
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );

            if( !isset( $responseJSON['historicalMovements'] ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['historicalMovements'];
        }

        //Método para hacer transferencias.
        public function transfer( $params ) {
            $transaction = [
                'transaction' => [
                    'sourceAccount' => $params['sourceAccount'],
                    'destinationAccount' => $params['destinationAccount'],
                    'transactionAmount' => number_format( $params['transactionAmount'], 2, '.', ''),
                    'description' => $params['description']
                ]
            ];

            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v1/sandbox/checking-accounts/transfer',
                'headers' => $this->headers
            ]);
            $response = $oCurl->post( json_encode( $transaction ) );
            $responseJSON = json_decode( $response, true );
            var_dump( isset( $responseJSON['transferResponse'] ) );
          var_dump( $responseJSON ); die();
            if( !isset( $responseJSON['transferResponse'] ) ) {
                $this->setError('Hubo un problema al generar la transferencia con HSBC.');
            }

            return $responseJSON['transferResponse'];
        }
    }
?>
