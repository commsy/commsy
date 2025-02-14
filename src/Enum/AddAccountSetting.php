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

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AddAccountSetting: string implements TranslatableInterface
{
    case YES = 'yes';
    case NO = 'no';
    case INVITATION = 'invitation';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(ucfirst($this->value), domain: 'portal');
    }
}
