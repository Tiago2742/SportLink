<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Le container Docker injecte APP_ENV=dev via docker-compose.yml, ce qui peuple
// $_ENV['APP_ENV']='dev'. PHPUnit's <server> ne met à jour que $_SERVER, mais
// Symfony's KernelTestCase::createKernel() lit $_ENV en premier.
// On synchronise ici pour que l'env PHPUnit gagne sur la valeur Docker.
if (isset($_SERVER['APP_ENV'])) {
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'];
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
