<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Commsy\LegacyBundle\Utils\RoomService;
use Symfony\Component\HttpFoundation\RequestStack;

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
                        ->add('group', 'filter_choice', array(
                            'translation_domain' => 'form'
                        ))
                    ;
                }

                // todo
                if (in_array('todo', $filterableRubrics)) {
                    $builder
                        ->add('todo', 'filter_choice', array(
                            'translation_domain' => 'form'
                        ))
                    ;
                }
                
                // institution
                if (in_array('institution', $filterableRubrics)) {
                    $builder
                        ->add('institution', 'filter_choice', array(
                            'translation_domain' => 'form'
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