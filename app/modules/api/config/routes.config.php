<?php
return [
    'get' => [
      ['uri' => '/banregio/callback-auth', 'handler' => 'banregio', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/callback-token', 'handler' => 'banregio', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/accounts', 'handler' => 'banregio', 'method' => 'obtenerCuentas', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/accounts/{idAccount}', 'handler' => 'banregio', 'method' => 'obtenerCuentas', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/accounts/{idAccount}/transancciones', 'handler' => 'banregio', 'method' => 'consultaTransacciones', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/clients/{clientNumber}/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByClient', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/balance', 'handler' => 'hsbc', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/account-statement', 'handler' => 'hsbc', 'method' => 'getCheckingAccountStatement', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/fin-lab/bank/{accountNumber}/balances', 'handler' => 'fin-lab', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/fin-lab/bank/{accountNumber}/transactions', 'handler' => 'fin-lab', 'method' => 'getTransactions', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/users/{idUser}/godfathers', 'handler' => 'usuarios', 'method' => 'getGodFathers', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/users/{idUser}/godsons', 'handler' => 'usuarios', 'method' => 'getGodSons', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/users/{idUser}/accounts', 'handler' => 'usuarios', 'method' => 'getAccounts', 'cors' => false, 'verifyToken' => true],
      ['uri' => '/retos', 'handler' => 'usuarios', 'method' => 'getRetos', 'cors' => false, 'verifyToken' => true],
      ['uri' => '/push-notifications', 'handler' => 'usuarios', 'method' => 'getPushNotifications', 'cors' => false, 'verifyToken' => true],
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => false, 'verifyToken' => false],
        ['uri' => '/hsbc/transfer', 'handler' => 'hsbc', 'method' => 'transfer', 'cors' => false, 'verifyToken' => true],
        ['uri' => '/transfer', 'handler' => 'usuarios', 'method' => 'transfer', 'cors' => false, 'verifyToken' => true],
        ['uri' => '/fin-lab/account/level2', 'handler' => 'fin-lab', 'method' => 'createAccount', 'cors' => false, 'verifyToken' => true],
        ['uri' => '/accounts', 'handler' => 'cuentas', 'method' => 'agregar', 'cors' => false, 'verifyToken' => true],
        ['uri' => '/reto', 'handler' => 'usuarios', 'method' => 'agregarReto', 'cors' => false, 'verifyToken' => true]
    ]
];
