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

namespace Tests\Application;

use App\Entity\Account;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;

/**
 * What happens once a check has said no. Who may see which room type is settled
 * in {@see \Tests\Unit\Room\RoomViewCheckerTest}; the types here only supply
 * one visible and one invisible case.
 *
 * A denial bounces to the room's detail page. That page may be denied in turn,
 * and the second pass used to end in 500: no room left in the request, so the
 * portal id went into the item slot and ItemVoter handed a PortalProxy to a
 * check typed against cs_item.
 */
final class AccessDeniedPathTest extends AbstractApplicationTestCase
{
    /**
     * A project room stays visible to any portal member, so the bounce lands on
     * its detail page, where membership can be asked for.
     */
    public function testTheBounceLandsOnTheDetailPageWhenThatPageIsVisible(): void
    {
        ['portal' => $portal, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();

        $room = RoomFactory::new()->project()->create([
            'contextId' => $portalId,
            'portal' => $portal,
        ]);

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', '/room/'.$room->getItemId().'/all');
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    /**
     * A group room is only visible from its linked project room, so the detail
     * page is denied too. The chain has to end in 403, not bounce on.
     */
    public function testTheChainEndsInForbiddenWhenTheDetailPageIsDeniedToo(): void
    {
        ['portal' => $portal, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();

        $room = RoomFactory::new()->groupRoom()->create([
            'contextId' => $portalId,
            'portal' => $portal,
        ]);

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', '/room/'.$room->getItemId().'/all');
        $this->client->followRedirect();

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * A portal id in the item slot — the URL the broken handler produced, and
     * reachable by hand since the id ranges overlap. Refused, never fatal.
     */
    public function testAPortalIdInTheItemSlotIsRefusedNotFatal(): void
    {
        ['portal' => $portal, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/room/{$portalId}");

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Without a session the entry point answers first, so nothing about the
     * room's existence is given away.
     */
    public function testAGuestIsSentToLoginAndLearnsNothing(): void
    {
        ['portal' => $portal] = $this->createScenario();
        $portalId = $portal->getId();

        $room = RoomFactory::new()->groupRoom()->create([
            'contextId' => $portalId,
            'portal' => $portal,
        ]);

        $this->client->request('GET', '/room/'.$room->getItemId().'/all');

        self::assertResponseRedirects('/login/'.$portalId);
    }

    /**
     * Error pages are reached for failures the firewall never sees, where there
     * may be no session. Checked on the templates: a real logged-out render is
     * not reachable here, the entry point redirects first.
     */
    public function testErrorPagesDoNotDependOnASession(): void
    {
        $templates = [
            'error/layout.html.twig',
            'bundles/TwigBundle/Exception/error403.html.twig',
            'bundles/TwigBundle/Exception/error404.html.twig',
            'bundles/TwigBundle/Exception/error.html.twig',
        ];

        foreach ($templates as $template) {
            $source = file_get_contents(__DIR__.'/../../templates/'.$template);
            self::assertIsString($source, "{$template} is missing");

            // Comments out: they name what the templates avoid.
            $code = preg_replace('/\{#.*?#\}/s', '', $source);

            foreach (['app.user', 'is_granted', 'render(controller'] as $sessionBound) {
                self::assertStringNotContainsString(
                    $sessionBound,
                    $code,
                    "{$template} must not use {$sessionBound}: a page that throws while "
                    .'reporting an error leaves the visitor with nothing'
                );
            }
        }
    }

    /**
     * Existence is itself information, and this page is reachable by typing a
     * URL, so it must stay generic.
     */
    public function testTheForbiddenPageNamesNothingItRefused(): void
    {
        ['portal' => $portal, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();

        $room = RoomFactory::new()->groupRoom()->create([
            'contextId' => $portalId,
            'portal' => $portal,
            'title' => 'Streng geheimer Gruppenraum',
        ]);

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', '/room/'.$room->getItemId().'/all');
        $crawler = $this->client->followRedirect();

        self::assertResponseStatusCodeSame(403);
        self::assertStringNotContainsString('Streng geheimer Gruppenraum', $crawler->text());
    }

    /**
     * An id that resolves to nothing must be answered like one that exists but
     * is closed — a difference is an oracle. It also must not reach
     * cs_environment, which used to raise E_USER_ERROR for such an id.
     */
    public function testAnUnknownRoomIdIsRefusedLikeAClosedOne(): void
    {
        ['portal' => $portal, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', '/room/9999999/all');

        // Straight to 403 where a closed room bounces first: no portal resolves
        // off an id that is not there. Same answer, different route — a known,
        // much narrower difference than the 500 it replaces.
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Same for a visitor without a session: a login page that renders.
     * /login/9999999 would answer 404 and give it away through the back door.
     */
    public function testAnUnknownRoomIdSendsAGuestToAWorkingLoginPage(): void
    {
        $this->createScenario();

        $this->client->request('GET', '/room/9999999/all');

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    /**
     * @return array{portal: mixed, stranger: mixed}
     */
    private function createScenario(): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $stranger = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'the.stranger',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        return ['portal' => $portal, 'stranger' => $stranger];
    }
}
