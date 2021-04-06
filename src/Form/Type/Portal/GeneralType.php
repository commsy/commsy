<?php

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
