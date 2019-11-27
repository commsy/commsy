<?php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Services\CalendarsService;

use App\Form\Type\CalendarType;

use Doctrine\ORM\EntityRepository;

class CalendarFilterType extends AbstractType
{
    private $requestStack;

    private $calendarService;

    public function __construct(RequestStack $requestStack, CalendarsService $calendarService)
    {
        $this->requestStack = $requestStack;
        $this->calendarService = $calendarService;
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

                $calendars = $this->calendarService->getListCalendars($roomId);

                $calendarsForm = [];
                foreach ($calendars as $calendar) {
                    $calendarsForm[$calendar->getTitle()] = $calendar->getId();
                }

                $builder
                    ->add('calendar', CalendarType::class, array(
                        'choices' => $calendarsForm,
                        'multiple' => true,
                        'expanded' => true,
                        'label' => false,
                        ));
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
        return 'calendar_filter';
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

}