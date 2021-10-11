<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
            ->setDefaults([
                'translation_domain' => 'form',
                'constraints' => [
                    new Callback([$this, 'validate']),
                ]
            ])
        ;
    }

    public function validate(array $data, ExecutionContextInterface $context): void
    {
        if (!$data['hashtags'] && !$data['newHashtag']) {
            $context->buildViolation('Please select at least one hashtag')
                ->atPath('hashtags')
                ->addViolation();
        }
    }
}
