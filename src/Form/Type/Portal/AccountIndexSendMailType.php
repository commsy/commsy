<?php


namespace App\Form\Type\Portal;

use App\Entity\AccountIndexSendMail;
use App\Entity\PortalUserEdit;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountIndexSendMailType extends AbstractType
{

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

        $builder
            ->add('copyToSender', ChoiceType::class, [
                'label' => 'Copy to sender',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'translation_domain' => 'mail',
                'choice_translation_domain' => 'mail',
                'required' => true,
                'data' => false,
            ])
            ->add('subject', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'Subject',
                'translation_domain' => 'mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Subject',
                ],
            ])
            ->add('names', TextType::class, [
                'label' => 'Names',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('message', CKEditorType::class, [
                'label' => 'message',
                'translation_domain' => 'form',
                'required' => true,
                'config_name' => 'cs_mail_config',
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Send',
                'translation_domain' => 'mail',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => 'formnovalidate',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'users' => [],
                'data_class' => AccountIndexSendMail::class,
                'item' => null,
                'translation_domain' => 'portal',
            ])
        ;
    }
}