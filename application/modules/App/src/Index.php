<?php

namespace App;

use Micro\Application\View;

class Index
{
    public function index()
    {
        return new View('index');
    }
}