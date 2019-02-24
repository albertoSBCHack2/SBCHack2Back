<?php
    require _APP . '/components/curl.component.php';

    class FinLabDomain extends BaseDomain {
        private $baseURL = 'https://dev.apilab.gentera.com.mx';

        //Método para obtener el balance por número de cuenta.
        public function getBalanceByAccount( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v2/bank/' . $params['accountNumber'] . '/balances'
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );
            
            if( !( isset( $responseJSON['data'] ) && isset( $responseJSON['data']['balance_result'] ) ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['data']['balance_result'];
        }

        //Método para obtener los movimientos.
        public function getTransactions( $params ) {
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/v2/bank/' . $params['accountNumber'] . '/transactions'
            ]);
            $response = $oCurl->get();
            $responseJSON = json_decode( $response, true );
            
            if( !( isset( $responseJSON['data'] ) && isset( $responseJSON['data']['statement_result'] ) ) ) {
                $this->setError('Cuenta no existe.');
            }

            return $responseJSON['data']['statement_result'];
        }

        //Método para crear cuentas nivel 2.
        public function createAccount( $params ) {
            $accountParams = [
                "MessageHeader" => [
                    "CreationDateTime" => "2001-01-03T21:34:11Z"
                ],
                "Level2AccountCreationData" => [
                    "Identifiers" => [
                        "ServiceOfficeID" => "242",
                        "CommerceID" => "J211",
                        "OriginID" => "Z06"
                    ],
                    "BusinessPartnerCreateLevel2AccountData" => [
                        "NameData" => [
                            "GivenName" => "Maria",
                            "MiddleName" => "carreto",
                            "FamilyName" => "Paz",
                            "AditionalFamilyName" => "Mentor"
                        ],
                        "BirthDate" => "1992-12-07",
                        "Gender" => "0",
                        "DocumentIdentifier" => [
                            "Code" => "ZCVELE",
                            "ID" => "ARMNJR92120714H700"
                        ],
                        "CardIdentification" => $params['cardIdentification'],
                        "RegionBirth" => "DF",
                        "BirthCountryCode" => "MX",
                        "CitizenshipCode" => "MX",
                        "PhoneData" => [
                            "PhoneTypeID" => "6",
                            "PhoneNumber" => [
                                "SubscriberID" => "5543783455"
                            ]
                        ],
                        "AddressData" => [
                            "AddressTypeCode" => "XXDEFAULT",
                            "StreetName" => "Canela",
                            "CountryCode" => "MX",
                            "RegionCode" => "DF",
                            "CityName" => "Mexico",
                            "DistrictName" => "Iztacalco",
                            "AdditionalCityName" => "Granjas Mexico",
                            "StreetPostalCode" => "08400",
                            "HouseID" => "484",
                            "AdditionalHouseID" => "C-203"
                        ]
                    ]
                ]
            ];

            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/account/level2',
                'headers' => [
                    'Content-Type: application/json'
                ]
            ]);
            $response = $oCurl->post( json_encode( $accountParams ) );
            $responseJSON = json_decode( $response, true );
 
            if( !( isset( $responseJSON['data'] ) && isset( $responseJSON['data']['MT_Level2AccountCreationResp_sync'] ) ) ) {
                $this->setError('El folio ingresado ya fue utilizado.');
            }

            $response = $responseJSON['data']['MT_Level2AccountCreationResp_sync']['Level2AccountCreationDataResponse'];
            
            //Ligamos esta cuenta con el usuario.
            $this->getModel('usuarios', 'cuentas')->agregar([
                'id_usuario' => $params['idUsuario'],
                'id_banco' => 2,
                'num_cuenta' => $response['BusinessPartnerIDCreated']
            ]);

            //Buscamos al padrino de este usuario.
            $padrinos = $this->getModel('usuarios', 'padrinos-ahijados')->obtenerPadrinos([
                'idUsuario' => $params['idUsuario']
            ]);

            //Enviamos push al padrino.
            foreach( $padrinos as $padrino ) {
                $this->getModel('usuarios', 'push-notification')->agregar([
                    'id_usuario' => $padrino['idUsuario'],
                    'mensaje' => 'Tu ahijado ' . $params['nombreAhijado'] . ' acaba de crear una nueva cuenta de ahorro.'
                ]);
            }

            return $response;
        }
    }
?>