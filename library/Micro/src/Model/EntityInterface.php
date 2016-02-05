<?php

namespace Micro\Model;

interface EntityInterface extends \ArrayAccess
{
    public function setFromArray(array $data);

    public function toArray();

    public function toJson();
}