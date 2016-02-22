<?php

namespace App\Controller;

use Micro\Application\View;
use Micro\Application\Controller;

class Index extends Controller
{
    public function indexAction()
    {
        return new View('index');
    }
}