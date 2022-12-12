<?php

use App\Kernel;

$appKernel = new Kernel('tests', false);
$appKernel->boot();

return $appKernel->getContainer();
