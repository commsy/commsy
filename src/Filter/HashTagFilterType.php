<?php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Doctrine\ORM\EntityRepository;

use App\Utils\RoomService;

use App\Form\Type\HashtagType;

class HashTagFilterType extends AbstractType
{
    private $requestStack;

    private $roomService;

    public function __construct(RequestStack $requestStack, RoomService $roomService)
    {
        $this->requestStack = $requestStack;
        $this->roomService = $roomService;
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

                $builder
                    ->add('hashtag', HashtagType::class, array(
                        'class' => 'CommsyBundle:Labels',
                        'query_builder' => function (EntityRepository $er) use ($roomId) {
                            return $er->createQueryBuilder('l')
                                ->andWhere('l.contextId = :roomId')
                                ->andWhere('l.type = :type')
                                ->andWhere('l.deletionDate IS NULL')
                                ->andWhere('l.deleter IS NULL')
                                ->orderBy('l.name')
                                ->setParameter('roomId', $roomId)
                                ->setParameter('type', 'buzzword');
                        },
                        'choice_label' => 'name',
                        'placeholder' => false,
                        'translation_domain' => 'form',
                        'expanded' => true,
                        'label' => false,
                    ))
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
    public function getBlockPrefix()
    {
        return 'hashtag_filter';
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering') // avoid NotBlank() constraint-related message
        ));
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