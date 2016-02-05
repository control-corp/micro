<?php

namespace Micro\Auth\Storage;

interface StorageInterface
{
    public function write($data);

    public function read();

    public function clear();
}