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
            ->add('submit', SubmitType::class, [
                'label' => 'portal.create',
                'attr' => [
                    'class' => 'uk-button-primary uk-width-medium',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Portal $portal */
            $portal = $event->getData();
            $form = $event->getForm();

            // Are editing an existing portal?
            if ($portal !== null && $portal->getId() !== null) {
                $form->add('logo', FileType::class, [
                    'mapped' => false,
                    'required' => false,
                ]);

                //        ->add('room_image_upload', FileType::class, array(
//            'attr' => array(
//                'required' => false,
//                'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
//            ),
                //'image_path' => 'webPath',
//    ))
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portal::class,
            'translation_domain' => 'portal',
        ]);
    }
}
