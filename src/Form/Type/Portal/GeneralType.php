<?php

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type as VichTypes;

class GeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'portal.form_title',
            ])
            ->add('descriptionGerman', CKEditorType::class, [
                'label' => 'portal.form_desc_de',
                'required' => false,
                'config_name' => 'cs_mail_config',
            ])
            ->add('descriptionEnglish', CKEditorType::class, [
                'label' => 'portal.form_desc_en',
                'required' => false,
                'config_name' => 'cs_mail_config',
            ])
            ->add('logoFile', VichTypes\VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'download_label' => true,
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'portal.create',
                'attr' => [
                    'class' => 'uk-button-primary uk-width-medium',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
