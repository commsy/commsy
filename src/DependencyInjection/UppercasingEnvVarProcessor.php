<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Closure;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class UppercasingEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, Closure $getEnv): mixed
    {
        $env = $getEnv($name);

        return strtoupper((string) $env);
    }

    public static function getProvidedTypes(): array
    {
        return [
            'uppercase' => 'string',
        ];
    }
}
