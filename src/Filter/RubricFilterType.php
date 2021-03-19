<?php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use App\Utils\RoomService;
use App\Repository\LabelRepository;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class RubricFilterType extends AbstractType
{
    private $roomService;
    private $requestStack;

    public function __construct(RoomService $roomService, RequestStack $requestStack)
    {
        $this->roomService = $roomService;
        $this->requestStack = $requestStack;
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
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
                        ->add('group', Filters\EntityFilterType::class, array(
                            'label' => 'group',
                            'class' => 'App:Labels',
                            'query_builder' => function (LabelRepository $er) use ($roomId) {
                                return $er->createQueryBuilder('l')
                                    ->andWhere('l.contextId = :contextId')
                                    ->andWhere('l.type = :type')
                                    ->andWhere('l.deletionDate IS NULL')
                                    ->andWhere('l.name != :all')
                                    ->setParameter('contextId', $roomId)
                                    ->setParameter('type', 'group')
                                    ->setParameter('all', 'ALL');
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
                        ->add('topic', Filters\EntityFilterType::class, array(
                            'label' => 'topic',
                            'class' => 'App:Labels',
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
                        ->add('institution', Filters\EntityFilterType::class, array(
                            'class' => 'App:Labels',
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

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'rubric_filter';
    }
}