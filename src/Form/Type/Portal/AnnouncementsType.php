<?php
namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints as Assert;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

class AnnouncementsType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('announcementEnabled' , Types\ChoiceType::class, [
                'label' => 'Show',
                'expanded' => true,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'choice_translation_domain' => 'form',
            ])
            ->add('announcementTitle', Types\TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'label' => 'Title',
            ])
            ->add('announcementSeverity', Types\ChoiceType::class, [
                'label' => 'Severity',
                'choices' => [
                    'Normal' => 'normal',
                    'Important' => 'warning',
                    'Critical' => 'danger',
                ],
            ])
            ->add('announcementText', CKEditorType::class, [
                'label' => 'Message',
                'translation_domain' => 'settings',
                'required' => false,
            ])
            ->add('announcementLink', Types\UrlType::class, [
                'constraints' => [
                    new Assert\Url(),
                ],
                'label' => 'Link',
                'required' => false,
            ])
            ->add('serverAnnouncementEnabled', Types\ChoiceType::class, [
                'label' => 'Show server infos',
                'expanded' => true,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'choice_translation_domain' => 'form',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
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
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}