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

use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomProfileType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    /**
     * @var mixed|null
     */
    private ?object $userItem = null;

    public function __construct(private readonly EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $this->userItem = $userManager->getItem($options['itemId']);

        $builder
            ->add('language', ChoiceType::class, ['placeholder' => false, 'choices' => ['browser' => 'browser', 'de' => 'de', 'en' => 'en'], 'label' => 'language', 'required' => false])
            ->add('autoSaveStatus', CheckboxType::class, ['label' => 'autoSaveStatus', 'required' => false, 'label_attr' => ['class' => 'uk-form-label']])

            ->add('title', TextType::class, ['label' => 'title', 'required' => false])
            ->add('titleChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])
            ->add('image', FileType::class, ['attr' => ['data-upload' => '{"path": "'.$options['uploadUrl'].'"}'], 'label' => 'image', 'required' => false])
            ->add('image_data', HiddenType::class, [])
            ->add('imageChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('email', TextType::class, ['label' => 'email', 'required' => false])
            ->add('emailChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('hideEmailInThisRoom', CheckboxType::class, ['label' => 'hideEmailInThisRoom', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'translation_domain' => 'user'])
            ->add('hideEmailInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('phone', TextType::class, ['label' => 'phone', 'required' => false])
            ->add('phoneChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('mobile', TextType::class, ['label' => 'mobile', 'required' => false])
            ->add('mobileChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('street', TextType::class, ['label' => 'street', 'required' => false])
            ->add('streetChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('zipcode', TextType::class, ['label' => 'zipcode', 'required' => false])
            ->add('zipcodeChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('city', TextType::class, ['label' => 'city', 'required' => false])
            ->add('cityChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('room', TextType::class, ['label' => 'room', 'required' => false])
            ->add('roomChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('organisation', TextType::class, ['label' => 'organisation', 'required' => false])
            ->add('organisationChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('position', TextType::class, ['label' => 'position', 'required' => false])
            ->add('positionChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('skype', TextType::class, ['label' => 'skype', 'required' => false])
            ->add('skypeChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('homepage', TextType::class, ['label' => 'homepage', 'required' => false])
            ->add('homepageChangeInAllContexts', CheckboxType::class, ['label' => 'changeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('description', TextareaType::class, ['attr' => ['cols' => '80', 'rows' => '10'], 'label' => 'description', 'required' => false])
            ->add('descriptionChangeInAllContexts', CheckboxType::class, ['label' => 'descriptionChangeInAllContexts', 'required' => false, 'label_attr' => ['class' => 'uk-form-label'], 'data' => true])

            ->add('save', SubmitType::class, ['label' => 'save', 'translation_domain' => 'form', 'attr' => ['class' => 'uk-button-primary']]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['itemId', 'uploadUrl'])
            ->setDefaults(['translation_domain' => 'profile'])
        ;
    }
}
