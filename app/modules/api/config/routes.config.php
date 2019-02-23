<?php
return [
    'get' => [
      ['uri' => '/banregio/banregio-callback-auth', 'handler' => 'banregio', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/banregio-callback-token', 'handler' => 'banregio', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio/obtener-cuentas', 'handler' => 'banregio', 'method' => 'obtenerCuentas', 'cors' => false, 'verifyToken' => false],
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => true, 'verifyToken' => false]
    ]
];
