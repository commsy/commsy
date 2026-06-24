<?php

declare(strict_types=1);

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

namespace Tests\Unit\Mail;

use App\Mail\MailTextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailTextResolverTest extends TestCase
{
    private function translator(string $return): TranslatorInterface
    {
        return new class($return) implements TranslatorInterface {
            /** @var array<string, mixed> */
            public array $captured = [];

            public function __construct(private readonly string $return)
            {
            }

            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $this->captured = ['id' => $id, 'parameters' => $parameters, 'domain' => $domain, 'locale' => $locale];

                return $this->return;
            }

            public function getLocale(): string
            {
                return 'de';
            }
        };
    }

    public function testPortalOverrideWinsAndReplacesLegacyParams(): void
    {
        $resolver = new MailTextResolver($this->translator('DEFAULT - must not be used'));
        $overrides = ['MAIL_BODY_HELLO' => ['DE' => 'Hallo %1,']];

        self::assertSame(
            'Hallo Max,',
            $resolver->resolveRaw('mail.salutation', 'MAIL_BODY_HELLO', 'portal', 'de', ['Max'], $overrides)
        );
    }

    public function testOverrideLookupIsCaseInsensitiveOnLanguage(): void
    {
        $resolver = new MailTextResolver($this->translator('DEFAULT'));
        $overrides = ['MAIL_BODY_HELLO' => ['en' => 'Dear %1,']];

        self::assertSame(
            'Dear Max,',
            $resolver->resolveRaw('mail.salutation', 'MAIL_BODY_HELLO', 'portal', 'en', ['Max'], $overrides)
        );
    }

    public function testFallsBackToTranslatorWithRoomTypeAndPositionalArgs(): void
    {
        $translator = $this->translator('Dear Max,');
        $resolver = new MailTextResolver($translator);

        $result = $resolver->resolveRaw('mail.salutation', 'MAIL_BODY_HELLO', 'community', 'en', ['Max']);

        self::assertSame('Dear Max,', $result);
        self::assertSame('mail.salutation', $translator->captured['id']);
        self::assertSame(['room_type' => 'community', 'p1' => 'Max'], $translator->captured['parameters']);
        self::assertSame('mail', $translator->captured['domain']);
        self::assertSame('en', $translator->captured['locale']);
    }

    public function testResolveNormalizesNewlinesToBreaks(): void
    {
        $resolver = new MailTextResolver($this->translator("Line1\nLine2"));

        self::assertSame('Line1<br/>Line2', $resolver->resolve('k', 'M', 'portal', 'de'));
    }

    public function testResolveTrimsWrappingParagraphBreaks(): void
    {
        $resolver = new MailTextResolver($this->translator('<p>Body</p>'));

        self::assertSame('Body', $resolver->resolve('k', 'M', 'portal', 'de'));
    }

    public function testResolveRawDoesNotNormalize(): void
    {
        $resolver = new MailTextResolver($this->translator("Line1\nLine2"));

        self::assertSame("Line1\nLine2", $resolver->resolveRaw('k', 'M', 'portal', 'de'));
    }
}
