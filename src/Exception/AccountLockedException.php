<?php


namespace App\Exception;


use Symfony\Component\Security\Core\Exception\AccountStatusException;

class AccountLockedException extends AccountStatusException
{

}