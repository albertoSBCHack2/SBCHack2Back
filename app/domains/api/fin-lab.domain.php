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
                'MessageHeader' => [
                    'CreationDateTime' => '2001-01-03T21 =>34 =>11Z'
                ],
                'Level2AccountCreationData' => [
                    'Identifiers' => [
                        'ServiceOfficeID' => '242',
                        'CommerceID' => 'J211',
                        'OriginID' => 'Z06'
                    ],
                    'BusinessPartnerCreateLevel2AccountData' => [
                        'NameData' => [
                            'GivenName' => $params['nombre'],
                            'MiddleName' => $params['apellidoPaterno'],
                            'FamilyName' => $params['apellidoMaterno'],
                            'AditionalFamilyName' => 'Mentor'
                        ],
                        'BirthDate' => $params['fechaNacimiento'],
                        'Gender' => '0',
                        'DocumentIdentifier' => [
                            'Code' => 'ZCVELE',
                            'ID' => $params['ine']
                        ],
                        'CardIdentification' => $params['cardIdentification'],
                        'RegionBirth' => 'DF',
                        'BirthCountryCode' => 'MX',
                        'CitizenshipCode' => 'MX',
                        'PhoneData' => [
                            'PhoneTypeID' => '6',
                            'PhoneNumber' => [
                                'SubscriberID' => $params['celular']
                            ]
                        ],
                        'AddressData' => [
                            'AddressTypeCode' => 'XXDEFAULT',
                            'StreetName' => 'Canela',
                            'CountryCode' => 'MX',
                            'RegionCode' => 'DF',
                            'CityName' => 'Mexico',
                            'DistrictName' => 'Iztacalco',
                            'AdditionalCityName' => 'Granjas Mexico',
                            'StreetPostalCode' => '08400',
                            'HouseID' => '484',
                            'AdditionalHouseID' => 'C-203'
                        ]
                    ]
                ]
            ];
            
            $oCurl = new CurlComponent([
                'url' => $this->baseURL . '/account/level2'
            ]);
            $response = $oCurl->post( json_encode( $accountParams ) );
            $responseJSON = json_decode( $response, true );
            return $responseJSON;
            if( !( isset( $responseJSON['data'] ) && isset( $responseJSON['data']['statement_result'] ) ) ) {
                $this->setError('Hubo un problema al crear la cuenta con FinLab.');
            }

            return $responseJSON['data']['statement_result'];
        }
    }
?>