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
use App\Form\Type\HashtagType;
use App\Utils\RoomService;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HashTagFilterType extends AbstractType
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RoomService $roomService
    ) {
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

                $builder
                    ->add('hashtag', HashtagType::class, ['class' => Labels::class, 'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('l')
                        ->andWhere('l.contextId = :roomId')
                        ->andWhere('l.type = :type')
                        ->andWhere('l.deletionDate IS NULL')
                        ->andWhere('l.deleter IS NULL')
                        ->orderBy('l.name')
                        ->setParameter('roomId', $roomId)
                        ->setParameter('type', 'buzzword'), 'choice_label' => 'name', 'placeholder' => false, 'translation_domain' => 'form', 'expanded' => true, 'label' => false, ])
                ;
            }
        }
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'hashtag_filter';
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['csrf_protection' => false, 'validation_groups' => ['filtering']]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $showExpanded = false;
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomItem = $this->roomService->getRoomItem($attributes->getInt('roomId'));
                $showExpanded = $roomItem->isBuzzwordShowExpanded();
            }
        }
        $view->vars['showExpanded'] = $showExpanded;
    }
}
