<?php
    /***
        Autor: Gil Verduzco
        Descripción: Constantes de las cuales se apoya lean framework.
    ***/

    //CONSTANTS.
    define( '_ROOT', __DIR__ . '/..' );
    define( '_LEAN', _ROOT . '/lean' );
    define( '_CONFIG', _ROOT . '/config' );
    
    // Classes
    define( '_AUTH', _LEAN . '/Auth.php' );
    define( '_BASE_CONTROLLER', _LEAN . '/BaseController.php' );
    define( '_BASE_DOMAIN', _LEAN . '/BaseDomain.php' );
    define( '_BASE_MODEL', _LEAN . '/BaseModel.php' );
    define( '_DBACCESS', _LEAN . '/DBAccess.php' );
    define( '_REQUEST', _LEAN . '/Request.php' );
    define( '_RESPONSE', _LEAN . '/Response.php' );
    define( '_ROUTER', _LEAN . '/Router.php' );
    define( '_APP', _ROOT . '/app' );
    define( '_MODULES', _APP . '/modules' );

    //Clases que tienen que estar cargadas inmediatamente después de definir las constantes.
    require_once _LEAN . '/Lean.php';
    require_once _LEAN . '/Logger.php';
    require_once _BASE_CONTROLLER;

    // External libaries
    require_once _ROOT.'/vendor/autoload.php';
?>
