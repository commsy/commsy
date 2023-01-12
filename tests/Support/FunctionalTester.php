<?php

namespace Tests\Support;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Facade\AccountCreatorFacade;
use App\Utils\UserService;
use Codeception\Actor;
use Codeception\Util\HttpCode;
use DateTimeImmutable;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;
}
