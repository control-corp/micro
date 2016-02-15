<?php

namespace App;

use Micro\Application\Package as BasePackage;
use Micro\Container\ContainerInterface;
use Micro\Application\Application;

class Package extends BasePackage
{
    public function boot(Application $app, ContainerInterface $container)
    {
        // get package config
        $config = include __DIR__ . '/configs/package.php';

        // merge with application config
        $container->get('config')
                  ->load($config);

        // register services
        $container->configure(
            $config['dependencies']
        );

        // middlewares
        $app->add($config['middleware']);

        // classmap
        \Microloader::addFiles(
            $config['microloader']['files']
        );
    }
}