<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Commsy\LegacyBundle\Utils\CategoryService;

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
                    ->add('category', 'category', array(
                        'choices' => $formCategories,
                        'multiple' => true,
                        'expanded' => true,
                        'label' => false,
                    ))
                ;
            }
        }
    }

    public function getName()
    {
        return 'category_filter';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
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