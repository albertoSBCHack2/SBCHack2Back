<?php
return [
    'get' => [
      ['uri' => '/banregio-callback-auth', 'handler' => 'api', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio-callback-token', 'handler' => 'api', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/clients/{clientNumber}/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByClient', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/balance', 'handler' => 'hsbc', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/account-statement', 'handler' => 'hsbc', 'method' => 'getCheckingAccountStatement', 'cors' => false, 'verifyToken' => false],
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => true, 'verifyToken' => false]
    ]
];
