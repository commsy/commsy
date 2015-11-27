<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Commsy\LegacyBundle\Utils\RoomService;
use CommsyBundle\Repository\LabelRepository;

class RubricFilterType extends AbstractType
{
    private $roomService;
    private $requestStack;

    public function __construct(RoomService $roomService, RequestStack $requestStack)
    {
        $this->roomService = $roomService;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // extract room id from request and build filter accordingly
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomId = $attributes->getInt('roomId');

                $filterableRubrics = $this->roomService->getFilterableRubrics($roomId);

                // group
                if (in_array('group', $filterableRubrics)) {
                    $builder
                        ->add('group', 'filter_entity', array(
                            'label' => 'group',
                            'attr' => array(
                                'onchange' => 'this.form.submit()',
                            ),
                            'class' => 'CommsyBundle:Labels',
                            'query_builder' => function (LabelRepository $er) use ($roomId) {
                                return $er->createQueryBuilder('l')
                                    ->andWhere('l.contextId = :contextId')
                                    ->andWhere('l.type = :type')
                                    ->andWhere('l.deletionDate IS NULL')
                                    ->setParameter('contextId', $roomId)
                                    ->setParameter('type', 'group');
                            },
                            'choice_label' => 'name',
                            'translation_domain' => 'form',
                            'placeholder' => 'no restrictions',
                            'choice_translation_domain' => true,
                        ))
                    ;
                }

                // todo
                if (in_array('topic', $filterableRubrics)) {
                    $builder
                        ->add('topic', 'filter_entity', array(
                            'label' => 'topic',
                            'attr' => array(
                                'onchange' => 'this.form.submit()',
                            ),
                            'class' => 'CommsyBundle:Labels',
                            'query_builder' => function (LabelRepository $er) use ($roomId) {
                                return $er->createQueryBuilder('l')
                                    ->andWhere('l.contextId = :contextId')
                                    ->andWhere('l.type = :type')
                                    ->andWhere('l.deletionDate IS NULL')
                                    ->setParameter('contextId', $roomId)
                                    ->setParameter('type', 'topic');
                            },
                            'choice_label' => 'name',
                            'translation_domain' => 'form',
                            'placeholder' => 'no restrictions',
                        ))
                    ;
                }
                
                // institution
                if (in_array('institution', $filterableRubrics)) {
                    $builder
                        ->add('institution', 'filter_entity', array(
                            'attr' => array(
                                'onchange' => 'this.form.submit()',
                            ),
                            'class' => 'CommsyBundle:Labels',
                            'query_builder' => function (LabelRepository $er) use ($roomId) {
                                return $er->createQueryBuilder('l')
                                    ->andWhere('l.contextId = :contextId')
                                    ->andWhere('l.type = :type')
                                    ->andWhere('l.deletionDate IS NULL')
                                    ->setParameter('contextId', $roomId)
                                    ->setParameter('type', 'institution');
                            },
                            'choice_label' => 'name',
                            'translation_domain' => 'form',
                            'placeholder' => 'no restrictions',
                        ))
                    ;
                }
            }
        }
    }

    public function getName()
    {
        return 'rubric_filter';
    }
}