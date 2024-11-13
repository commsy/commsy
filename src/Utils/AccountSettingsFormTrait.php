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

namespace App\Utils;

use App\Account\AccountSetting as AccountSettingEnum;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use Symfony\Contracts\Service\Attribute\Required;

trait AccountSettingsFormTrait
{
    private AccountSettingsManager $settingsManager;

    #[Required]
    public function setAccountSettingsManager(AccountSettingsManager $settingsManager): void
    {
        $this->settingsManager = $settingsManager;
    }

    public function getSetting(Account $account, AccountSettingEnum $setting): array
    {
        return $this->settingsManager->getSetting($account, $setting);
    }

    public function storeSetting(Account $account, AccountSettingEnum $setting, array $value): self
    {
        $this->settingsManager->storeSetting($account, $setting, $value);

        return $this;
    }

    public function removeSetting(Account $account, AccountSettingEnum $setting): self
    {
        $this->settingsManager->removeSetting($account, $setting);

        return $this;
    }
}
