<?php
namespace App\Form\Type;

use App\Validator\Constraints\UniquePortfolioCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PortfolioEditCategoryType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    /**
     * PortfolioType constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $this->buildChoices($options['categories']);

        $builder
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $choices,
                'translation_domain' => 'portfolio',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Count([
                        'min' => 1,
                        'max' => 1,
                    ]),
                    new UniquePortfolioCategory([
                        'portfolioId' => $options['portfolioId'],
                    ])
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => $this->translator->trans('Insert description here', [], 'portfolio'),
                ],
                'required' => false,
                'translation_domain' => 'portfolio',
            ])
            ->add('delete-category', HiddenType::class, [
                'label' => false,
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'validation_groups' => false,
            ])
        ;

        // Event listener for modifications based on the underlying data
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            if (is_numeric($options['categoryId'])) {
                $form
                    ->add('delete', SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-danger',
                        ],
                    ])
                ;
            }
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['categories', 'categoryId', 'portfolioId'])
            ->setDefaults(['translation_domain' => 'form'])
        ;
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
        return 'portfolio';
    }

    private function buildChoices($categories) {
        $choices = [];

        foreach ($categories as $category) {
            $choices[$category['title']] = $category['item_id'];

            if (!empty($category['children'])) {
                $choices[$category['title'] . 'sub'] = $this->buildChoices($category['children']);
            }
        }

        return $choices;
    }
}