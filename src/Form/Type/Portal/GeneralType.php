<?php

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Vich\UploaderBundle\Form\Type as VichTypes;

class GeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', Types\TextType::class, [
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
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
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
