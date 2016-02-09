<?php

$config = include __DIR__ . '/index-filters.php';

unset($config['elements']['months']);

return $config;