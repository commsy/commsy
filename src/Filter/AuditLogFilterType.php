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

namespace App\Filter;

use App\Audit\AuditEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\EnumFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Narrows the audit log. The navigation leads to the unfiltered list — picking
 * out a single kind of event happens here.
 */
class AuditLogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', EnumFilterType::class, [
                'class' => AuditEvent::class,
                'choice_label' => fn (AuditEvent $event): string => $event->labelKey(),
                'label' => 'portal.audit.filter.event',
                'placeholder' => 'All',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('occurredAt', DateRangeFilterType::class, [
                'label' => 'portal.audit.filter.period',
                'translation_domain' => 'portal',
                'required' => false,
                // The range widget renders the two inputs without their labels,
                // so the placeholder is what tells them apart.
                'left_date_options' => [
                    'label' => 'portal.audit.filter.period_from',
                    'widget' => 'single_text',
                    'html5' => false,
                    'attr' => ['placeholder' => 'portal.audit.filter.period_from'],
                ],
                'right_date_options' => [
                    'label' => 'portal.audit.filter.period_until',
                    'widget' => 'single_text',
                    'html5' => false,
                    'attr' => ['placeholder' => 'portal.audit.filter.period_until'],
                ],
            ])
            ->add('search', TextFilterType::class, [
                'label' => 'portal.audit.filter.search',
                'help' => 'portal.audit.filter.search_help',
                'translation_domain' => 'portal',
                'required' => false,
                // Both parties, and both by login name and by person's name —
                // whoever reads the log knows one of the four, not which column
                // it sits in.
                'apply_filter' => function (QueryInterface $filterQuery, string $field, array $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $expr = $filterQuery->getExpr();
                    $queryBuilder = $filterQuery->getQueryBuilder();

                    $queryBuilder
                        ->andWhere($expr->orX(
                            $expr->like('e.actorUsername', ':needle'),
                            $expr->like('e.actorName', ':needle'),
                            $expr->like('e.subjectLabel', ':needle'),
                            $expr->like('e.subjectName', ':needle')
                        ))
                        ->setParameter('needle', '%' . $values['value'] . '%');

                    return $queryBuilder;
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search',
                'translation_domain' => 'portal',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'get',
            'translation_domain' => 'form',
        ]);
    }
}
