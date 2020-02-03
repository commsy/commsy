<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 16:00
 */

namespace App\Mail;


interface MessageInterface
{
    public function getSubject(): string;

    public function getTemplateName(): string;

    public function getParameters(): array;

    public function getTranslationParameters(): array;
}