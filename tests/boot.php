<?php

chdir(dirname(__DIR__));

include 'library/Micro/autoload.php';

if (is_file($composer = 'vendor/autoload.php')) {
    include $composer;
}

MicroLoader::register();