<?php
return [
    'get' => [
      ['uri' => '/banregio-callback-auth', 'handler' => 'api', 'method' => 'obtenerAuthToken', 'cors' => false, 'verifyToken' => false],
      ['uri' => '/banregio-callback-token', 'handler' => 'api', 'method' => 'obtenerToken', 'cors' => false, 'verifyToken' => false],
    ],
    'post' => [

    ]
];
