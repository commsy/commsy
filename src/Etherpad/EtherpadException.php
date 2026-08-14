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

declare(strict_types=1);

namespace App\Etherpad;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * An Etherpad API call that did not succeed.
 *
 * Etherpad answers errors with HTTP 200 and reports them in the body, so a
 * transport-level check says nothing about the outcome. Every non-zero
 * response code arrives here instead of being folded into a null return.
 */
#[Exclude]
final class EtherpadException extends RuntimeException
{
    public const CODE_OK = 0;
    public const CODE_WRONG_PARAMETERS = 1;
    public const CODE_INTERNAL_ERROR = 2;
    public const CODE_NO_SUCH_FUNCTION = 3;
    public const CODE_NO_OR_WRONG_API_KEY = 4;

    private function __construct(
        string $message,
        public readonly ?int $apiCode = null,
        public readonly ?string $apiMessage = null,
    ) {
        parent::__construct($message);
    }

    public static function fromApiResponse(string $method, int $code, ?string $message): self
    {
        return new self(
            sprintf('Etherpad rejected "%s": %s (code %d)', $method, $message ?? 'no message', $code),
            $code,
            $message
        );
    }

    public static function transportFailed(string $method, \Throwable $previous): self
    {
        $exception = new self(sprintf('Etherpad is not reachable for "%s": %s', $method, $previous->getMessage()));

        return $exception;
    }

    public static function notConfigured(string $method): self
    {
        return new self(sprintf('Etherpad is not configured, cannot call "%s"', $method));
    }

    /**
     * Whether the call failed only because the pad is already there.
     *
     * Creating a pad is how this integration also "finds" one, since the pad
     * name is derived from the item — so this particular refusal is a normal
     * outcome rather than a fault.
     */
    public function meansPadAlreadyExists(): bool
    {
        return $this->apiCode === self::CODE_WRONG_PARAMETERS
            && $this->apiMessage !== null
            && str_contains($this->apiMessage, 'already exist');
    }
}
