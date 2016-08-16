<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CombineProfileType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $userItem;

    public function __construct(EntityManager $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
        $userManager = $this->legacyEnvironment->getUserManager();
        $this->userItem = $userManager->getItem($options['itemId']);

        $builder->add('combineUserId', TextType::class, array(
                'label' => 'combineUserId',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('combinePassword', TextType::class, array(
                'label' => 'combinePassword',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
            ));
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['itemId'])
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
        return 'combine_profile';
    }
    
}