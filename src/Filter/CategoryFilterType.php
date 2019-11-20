<?php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use App\Utils\CategoryService;
use App\Utils\RoomService;

use App\Form\Type\CategoryType;

use Doctrine\ORM\EntityRepository;

class CategoryFilterType extends AbstractType
{
    private $requestStack;

    private $categoryService;

    private $roomService;

    public function __construct(RequestStack $requestStack, CategoryService $categoryService, RoomService $roomService)
    {
        $this->requestStack = $requestStack;
        $this->categoryService = $categoryService;
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

                $categories = $this->categoryService->getTags($roomId);
                $formCategories = $this->transformTagArray($categories);

                $builder
                    ->add('category', CategoryType::class, array(
                        'choices' => $formCategories,
                        'multiple' => true,
                        'expanded' => true,
                        'label' => false,
                        ));
                $builder
                    ->add('submit', SubmitType::class, array(
                        'attr' => array(
                            'class' => 'uk-button-primary',
                            ),
                        'label' => 'Filter',
                        'translation_domain' => 'form',
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
        return 'category_filter';
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

    private function transformTagArray($tagArray)
    {
        $array = [];

        foreach ($tagArray as $tag) {
            $array[$tag['title']] = $tag['item_id'];

            if (!empty($tag['children'])) {
                $array[$tag['title'] . 'sub'] = $this->transformTagArray($tag['children']);
            }
        }

        return $array;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $showExpanded = false;
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $attributes = $currentRequest->attributes;
            if ($attributes->has('roomId')) {
                $roomItem = $this->roomService->getRoomItem($attributes->getInt('roomId'));
                $showExpanded = $roomItem->isTagsShowExpanded();
            }
        }
        $view->vars['showExpanded'] = $showExpanded;
    }
}