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

namespace App\Filter;

use App\Entity\Labels;
use App\Repository\LabelRepository;
use App\Utils\RoomService;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RubricFilterType extends AbstractType
{
    public function __construct(private readonly RoomService $roomService, private readonly RequestStack $requestStack)
    {
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // extract room id from request and build filter accordingly
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomId = $attributes->getInt('roomId');

                $filterableRubrics = $this->roomService->getFilterableRubrics($roomId, $currentRequest);

                // group
                if (in_array('group', $filterableRubrics)) {
                    $builder
                        ->add('group', Filters\EntityFilterType::class, ['label' => 'group', 'class' => Labels::class, 'query_builder' => fn (LabelRepository $er) => $er->createQueryBuilder('l')
                            ->andWhere('l.contextId = :contextId')
                            ->andWhere('l.type = :type')
                            ->andWhere('l.deletionDate IS NULL')
                            ->andWhere('l.name != :all')
                            ->setParameter('contextId', $roomId)
                            ->setParameter('type', 'group')
                            ->setParameter('all', 'ALL'), 'choice_label' => 'name', 'translation_domain' => 'form', 'placeholder' => 'no restrictions', 'choice_translation_domain' => true, ])
                    ;
                }

                // todo
                if (in_array('topic', $filterableRubrics)) {
                    $builder
                        ->add('topic', Filters\EntityFilterType::class, ['label' => 'topic', 'class' => Labels::class, 'query_builder' => fn (LabelRepository $er) => $er->createQueryBuilder('l')
                            ->andWhere('l.contextId = :contextId')
                            ->andWhere('l.type = :type')
                            ->andWhere('l.deletionDate IS NULL')
                            ->setParameter('contextId', $roomId)
                            ->setParameter('type', 'topic'), 'choice_label' => 'name', 'translation_domain' => 'form', 'placeholder' => 'no restrictions', ])
                    ;
                }

                // institution
                if (in_array('institution', $filterableRubrics)) {
                    $builder
                        ->add('institution', Filters\EntityFilterType::class, ['class' => Labels::class, 'query_builder' => fn (LabelRepository $er) => $er->createQueryBuilder('l')
                            ->andWhere('l.contextId = :contextId')
                            ->andWhere('l.type = :type')
                            ->andWhere('l.deletionDate IS NULL')
                            ->setParameter('contextId', $roomId)
                            ->setParameter('type', 'institution'), 'choice_label' => 'name', 'translation_domain' => 'form', 'placeholder' => 'no restrictions', ])
                    ;
                }
            }
        }
    }
}
