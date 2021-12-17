<?php


namespace App\Exception;


use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class AccountLockedException extends CustomUserMessageAuthenticationException
{

}