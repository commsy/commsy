<?php


namespace App\DependencyInjection;


use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class UppercasingEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return strtoupper($env);
    }

    public static function getProvidedTypes()
    {
        return [
            'uppercase' => 'string',
        ];
    }
}