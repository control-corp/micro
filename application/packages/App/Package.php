<?php

namespace App;

use Micro\Application\Package as Base;

class Package extends Base
{
    public function boot()
    {
        $this->container->get('event')->attach('application.start', function ($m) {

        });

        $this->container->get('event')->attach('route.end', function ($m) {

        });

        $this->container->get('event')->attach('render.start', function ($m) {

        });

        $this->container->get('event')->attach('application.end', function ($m) {

        });
    }
}