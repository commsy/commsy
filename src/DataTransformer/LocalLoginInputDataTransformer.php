<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\LocalLoginInput;
use App\Entity\Account;

class LocalLoginInputDataTransformer implements DataTransformerInterface
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function transform($object, string $to, array $context = [])
    {
        /** @var LocalLoginInput $object */
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

        return $to === Account::class && null !== ($context['input']['class'] ?? null);
    }
}