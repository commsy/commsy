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

namespace App\Notification;

use App\Entity\Notification;
use App\Rubric\Label\LabelType;
use App\Rubric\RubricType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves the room-entry URL a notification should open. Maps the snapshotted
 * rubric type to its detail route; every detail route generates from
 * (roomId, itemId) alone (material's versionId is optional). Returns null when
 * the type is unknown or the notification carries no source item, so callers
 * can fall back to the list.
 */
class NotificationLinkResolver
{
    private const DETAIL_ROUTES = [
        RubricType::Announcement->value => 'app_announcement_detail',
        RubricType::Material->value => 'app_material_detail',
        RubricType::Date->value => 'app_date_detail',
        RubricType::Discussion->value => 'app_discussion_detail',
        RubricType::Todo->value => 'app_todo_detail',
        LabelType::Group->value => 'app_group_detail',
        LabelType::Topic->value => 'app_topic_detail',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function resolve(Notification $notification): ?string
    {
        $route = self::DETAIL_ROUTES[$notification->getSourceItemType()] ?? null;
        $itemId = $notification->getSourceItemId();

        if ($route === null || $itemId === null) {
            return null;
        }

        return $this->urlGenerator->generate($route, [
            'roomId' => $notification->getContextId(),
            'itemId' => $itemId,
        ]);
    }
}
