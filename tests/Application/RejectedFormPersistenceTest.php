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

use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Regression test for rejected form values reaching the database.
 *
 * Forms bind to managed entities, and Symfony maps submitted values onto them before
 * validating. A controller that rejects a form therefore leaves the entity dirty and
 * relies on nobody flushing afterwards. Any listener that does — logging, activity
 * counters, metrics — commits those rejected values along with its own row, because
 * flush() covers the whole unit of work rather than the entity being saved.
 *
 * Scope, so the green tick is not read as more than it is: this exercises one request,
 * a rejected portal settings form, and therefore only the hooks that run on that path
 * — LoggingSubscriber and the portal branch of ActivitySubscriber. It says nothing
 * about the room branch, about paths LoggingSubscriber skips (ajax), or about hooks
 * that fire under conditions this request does not meet. The failure mode is general;
 * the coverage is not.
 *
 * Closing the class of problem would mean binding forms to DTOs rather than to managed
 * entities, so a rejected form cannot dirty anything in the first place.
 */
class RejectedFormPersistenceTest extends AbstractApplicationTestCase
{
    #[WithStory(PortalStory::class)]
    public function testRejectedPortalSettingsAreNotPersistedByLaterFlushes(): void
    {
        $portal = PortalStory::get('portal');
        $this->loginAsRoot();

        $crawler = $this->client->request('GET', "/portal/{$portal->getId()}/settings/general");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('portal_general[save]')->form();
        $form['portal_general[baseUrl]'] = 'kein gueltiger wert';
        $this->client->submit($form);

        // the form was rejected rather than saved
        $this->assertResponseStatusCodeSame(422);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $stored = $entityManager->getRepository(Portal::class)->find($portal->getId());

        self::assertNull(
            $stored->getBaseUrl(),
            'a rejected form value reached the database — a hook on this request path flushed the shared unit of work'
        );
    }
}
