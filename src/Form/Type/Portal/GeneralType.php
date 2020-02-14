<?php

namespace App\Form\Type\Portal;

use App\Entity\Portal;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('logo', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('description_de', CKEditorType::class, [
                'label' => 'Beschreibung DE',
                'required' => false,
                'mapped' => false,
            ])
            ->add('description_en', CKEditorType::class, [
                'label' => 'Beschreibung EN',
                'required' => false,
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class)
        ;


//        ->add('room_image_upload', FileType::class, array(
//            'attr' => array(
//                'required' => false,
//                'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
//            ),
        //'image_path' => 'webPath',
//    ))
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
        ]);
    }
}
