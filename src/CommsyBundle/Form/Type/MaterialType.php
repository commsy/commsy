<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;
use CommsyBundle\Form\Type\Custom\MandatoryCategoryMappingType;
use CommsyBundle\Form\Type\Custom\MandatoryHashtagMappingType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;
use CommsyBundle\Form\Type\Event\AddEtherpadFormListener;

class MaterialType extends AbstractType
{
    private $etherpadFormListener;

    public function __construct(AddEtherpadFormListener $etherpadListener)
    {
        $this->etherpadFormListener = $etherpadListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'title',
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'material',
            ))
            ->add('permission', CheckboxType::class, array(
                'label' => 'permission',
                'required' => false,
                'label_attr' => array('class' => 'uk-form-label'),
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'hidden',
                'required' => false,
            ))
            ->add('hiddendate', DateTimeSelectType::class, array(
                'label' => 'hidden until',
            ))
            ->addEventSubscriber($this->etherpadFormListener)
            ->add('biblio_select', ChoiceType::class, array(
                'choices'  => array(
                    'plain' => 'BiblioPlainType',
                    'book' => 'BiblioBookType',
                    'collection' => 'BiblioCollectionType',
                    'article' => 'BiblioArticleType',
                    'journal' => 'BiblioJournalType',
                    'chapter' => 'BiblioChapterType',
                    'newspaper' => 'BiblioNewspaperType',
                    'thesis' => 'BiblioThesisType',
                    'manuscript' => 'BiblioManuscriptType',
                    'website' => 'BiblioWebsiteType',
                    'document management' => 'BiblioDocManagementType',
                    'picture' => 'BiblioPictureType'
                ),
                'label' => 'bib reference',
                'choice_translation_domain' => true,
                'required' => false,
            ))
            ->addEventSubscriber(new AddBibliographicFieldListener())
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $material = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();

                if ($material['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
                }

                if ($material['draft']) {
                    if ($material['hashtagsMandatory'] && $formOptions['hashtagMappingOptions']) {
                        $form->add('hashtag_mapping', MandatoryHashtagMappingType::class, $formOptions['hashtagMappingOptions']);
                    }
                    if ($material['categoriesMandatory'] && $formOptions['categoryMappingOptions']) {
                        $form->add('category_mapping', MandatoryCategoryMappingType::class, $formOptions['categoryMappingOptions']);
                    }
                }
            })
            ->add('license_id', ChoiceType::class, array(
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' => $options['licenses'],
                'translation_domain' => 'material',
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
            ))
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
            ->setRequired(['placeholderText', 'hashtagMappingOptions', 'categoryMappingOptions', 'licenses'])
            ->setDefaults(array('translation_domain' => 'form'))
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
        return 'material';
    }
}