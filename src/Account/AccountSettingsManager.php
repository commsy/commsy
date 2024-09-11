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

namespace App\Account;

use App\Entity\Account;
use App\Entity\AccountSetting;
use App\Account\AccountSetting as AccountSettingEnum;

class AccountSettingsManager
{
    public function getSetting(Account $account, AccountSettingEnum $setting): array
    {
        $accountSettings = $account->getSettings();

        $lookup = $accountSettings->filter(fn (AccountSetting $settings) =>
            $settings->getName() === $setting->value);

        return !$lookup->isEmpty() ? $lookup->first()->getValue() : $setting->default();
    }

    public function storeSetting(Account $account, AccountSettingEnum $setting, array $value): void
    {
        $accountSetting = new AccountSetting();
        $accountSetting->setName($setting->value);
        $accountSetting->setValue($value);

        $account->setSetting($accountSetting);
    }
}
