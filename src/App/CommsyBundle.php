<?php

namespace App;

use App\DependencyInjection\Compiler\DatabaseChecksPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CommsyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DatabaseChecksPass());
    }
}
