<?php

declare(strict_types=1);

use Symfony\Component\Filesystem\Filesystem;

require \dirname(__DIR__) . '/vendor/autoload.php';

(new Filesystem())->remove(__DIR__ . '/../var');
