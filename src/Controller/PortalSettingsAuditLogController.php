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

namespace App\Controller;

use App\Entity\Portal;
use App\Filter\AuditLogFilterType;
use App\Repository\AuditLogEntryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Reads the audit log of one portal.
 *
 * Its own controller rather than another action in PortalSettingsController:
 * that class is past 2300 lines, and this view shares nothing with it.
 */
class PortalSettingsAuditLogController extends AbstractController
{
    #[Route(path: '/portal/{portalId}/settings/auditLog', name: 'app_portalsettings_auditlog')]
    #[IsGranted('PORTAL_MODERATOR', subject: 'portal')]
    public function index(
        #[MapEntity(id: 'portalId')]
        Portal $portal,
        Request $request,
        PaginatorInterface $paginator,
        AuditLogEntryRepository $auditLogEntries,
        FilterBuilderUpdaterInterface $filterBuilderUpdater
    ): Response {
        // Scoped to the portal in the url before the filter is applied, so no
        // filter value can widen it to another portal's entries.
        $queryBuilder = $auditLogEntries->createPortalQueryBuilder($portal);

        $filterForm = $this->createForm(AuditLogFilterType::class);

        if ($request->query->has($filterForm->getName())) {
            $filterForm->submit($request->query->all($filterForm->getName()));

            $filterBuilderUpdater->addFilterConditions($filterForm, $queryBuilder);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('portal_settings/audit_log.html.twig', [
            'portal' => $portal,
            'filterForm' => $filterForm,
            'pagination' => $pagination,
        ]);
    }
}
