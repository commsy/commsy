<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 15:43
 */

namespace App\Mail;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class Message implements MessageInterface
{
}