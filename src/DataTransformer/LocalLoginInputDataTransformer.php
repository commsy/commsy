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

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\LocalLoginInput;
use App\Entity\Account;

class LocalLoginInputDataTransformer implements DataTransformerInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function transform($object, string $to, array $context = [])
    {
        /* @var LocalLoginInput $object */
        $this->validator->validate($object);

        $account = new Account();
        $account->setContextId($object->getContextId());
        $account->setUsername($object->getUsername());
        $account->setPassword($object->getPassword());

        return $account;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Account) {
            return false;
        }

        return Account::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
