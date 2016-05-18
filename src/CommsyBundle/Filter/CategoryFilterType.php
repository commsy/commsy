<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Commsy\LegacyBundle\Utils\CategoryService;

use CommsyBundle\Form\Type\CategoryType;

use Doctrine\ORM\EntityRepository;

class CategoryFilterType extends AbstractType
{
    private $requestStack;

    private $categoryService;

    public function __construct(RequestStack $requestStack, CategoryService $categoryService)
    {
        $this->requestStack = $requestStack;
        $this->categoryService = $categoryService;
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
            $array[$tag['item_id']] = $tag['title'];

            if (!empty($tag['children'])) {
                $array[$tag['item_id'] . 'sub'] = $this->transformTagArray($tag['children']);
            }
        }

        return $array;
    }
}