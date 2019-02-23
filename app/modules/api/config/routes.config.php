<?php
return [
    'get' => [
       
    ],
    'post' => [
        ['uri' => '/login', 'handler' => 'usuarios', 'method' => 'logIn', 'cors' => true, 'verifyToken' => false]
    ]
];
