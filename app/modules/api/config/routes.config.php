<?php
return [
    'get' => [
      ['uri' => '/banregio/banregio-callback-auth', 'handler' => 'banregio', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/banregio-callback-token', 'handler' => 'banregio', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/accounts', 'handler' => 'banregio', 'method' => 'obtenerCuentas', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/clients/{clientNumber}/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByClient', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/profile', 'handler' => 'hsbc', 'method' => 'getProfileClientByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/balance', 'handler' => 'hsbc', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/hsbc/checking-accounts/account-statement', 'handler' => 'hsbc', 'method' => 'getCheckingAccountStatement', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/fin-lab/bank/{accountNumber}/balances', 'handler' => 'fin-lab', 'method' => 'getBalanceByAccount', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/fin-lab/bank/{accountNumber}/transactions', 'handler' => 'fin-lab', 'method' => 'getTransactions', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/accounts/{idUsuario}', 'handler' => 'usuarios', 'method' => 'getAccounts', 'cors' => false, 'verifyToken' => false],
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => true, 'verifyToken' => false],
        ['uri' => '/hsbc/transfer', 'handler' => 'hsbc', 'method' => 'transfer', 'cors' => true, 'verifyToken' => false],
        ['uri' => '/fin-lab/account/level2', 'handler' => 'fin-lab', 'method' => 'createAccount', 'cors' => true, 'verifyToken' => false],
        ['uri' => '/accounts', 'handler' => 'cuentas', 'method' => 'agregar', 'cors' => true, 'verifyToken' => true]
    ]
];
