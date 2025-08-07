<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type\Profile;

use App\Form\Type\UploadDropzoneType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class RoomProfileGeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('useProfileImage', CheckboxType::class, [
                'required' => false,
                'label_attr' => ['class' => 'uk-form-label'],
            ])
            ->addDependent('upload', 'useProfileImage', function (DependentField $field, bool $checked) use ($options) {
                if ($checked) {
                    $field->add(UploadDropzoneType::class, [
                        'uploadUrl' => $options['uploadUrl'],
                        'mapped' => false,
                    ]);
                }
            })
            ->addDependent('crop', 'useProfileImage', function (DependentField $field, ?bool $checked) use ($options) {
                /**
                 * Workaround for https://github.com/symfony/ux/pull/2397
                 * We always add the field for now and just control the visibility.
                 */
//                if (!$checked || !file_exists($options['cropPath'])) {
//                    return;
//                }

                $field->add(CropperType::class, [
                    'public_url' => $options['cropPublicUrl'],
                    'cropper_options' => [
                        'aspectRatio' => 1,
                        'preview' => '#cropper-preview',
                    ],
                    'attr' => [
                        'class' => !file_exists($options['cropPath']) || !$checked ? 'uk-hidden' : '',
                    ]
                ]);
            })
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => ['class' => 'uk-button-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['uploadUrl', 'cropPublicUrl', 'cropPath'])
            ->setDefaults(['translation_domain' => 'profile'])
        ;
    }
}
