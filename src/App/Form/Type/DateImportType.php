<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Symfony\Component\Translation\TranslatorInterface;

class DateImportType extends AbstractType
{
    /**
     * The Symfony translator
     * @var TranslatorInterface $translator
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $uploadErrorMessage = $this->translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $this->translator->trans('upload error', [], 'error');

        $builder
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedFileType::class,
                'entry_options' => [
                ],
            ])
            ->add('upload', FileType::class, array(
                'label' => 'upload',
                'attr' => array(
                    'data-uk-csupload' => '{"path": "' . $options['uploadUrl'] . '", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}',
                    "accept" => "text/calendar",
                ),
                'required' => false,
                'translation_domain' => 'date',
                'multiple' => false,
            ))
            ->add('calendar', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['calendars'],
                'choice_attr' => $options['calendarsAttr'],
                'label' => 'calendar',
                'required' => true,
                'expanded' => false,
                'multiple' => false
            ))
            ->add('calendartitle', TextType::class, array(
                'label' => 'Title',
                'translation_domain' => 'calendar',
                'required' => false,
            ))
            ->add('calendarcolor', TextType::class, [
                'label' => 'Color',
                'translation_domain' => 'calendar',
                'required' => false,
                'attr' => array(
                    'class' => 'jscolor {hash:true}',
                ),
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'import dates',
                'translation_domain' => 'date',
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
            ->setRequired(array('uploadUrl', 'calendars', 'calendarsAttr'))
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'upload';
    }
}