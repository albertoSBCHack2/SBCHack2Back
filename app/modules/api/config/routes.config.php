<?php
return [
    'get' => [
      ['uri' => '/banregio/banregio-callback-auth', 'handler' => 'banregio', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/banregio-callback-token', 'handler' => 'banregio', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/accounts', 'handler' => 'banregio', 'method' => 'obtenerCuentas', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/clients/{clientNumber}/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByClient', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/balance', 'handler' => 'hsbc', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => true, 'verifyToken' => false]
    ]
];
