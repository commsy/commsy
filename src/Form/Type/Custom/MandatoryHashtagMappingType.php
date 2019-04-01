<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Validator\Constraints\Count;

use Symfony\Component\OptionsResolver\OptionsResolver;

class MandatoryHashtagMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hashtags', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['hashtags'],
                'label' => 'hashtags',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'constraints' => array(
                    new Count(array('min' => 1, 'minMessage' => "Please select at least one hashtag")),
                ),
            ))
            ->add('newHashtag', TextType::class, array(
                'attr' => array(
                    'placeholder' => $options['hashTagPlaceholderText'],
                ),
                'label' => 'newHashtag',
                'required' => false
            ))
            ->add('newHashtagAdd', ButtonType::class, array(
                'attr' => array(
                    'id' => 'addNewHashtag',
                    'data-cs-add-hashtag' => $options['hashtagEditUrl'],
                ),
                'label' => 'addNewHashtag',
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
            ->setRequired(['hashTagPlaceholderText', 'hashtags', 'hashtagEditUrl'])
            ->setDefaults(array('translation_domain' => 'form'))
        ;
    }

}
