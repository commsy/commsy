<?php
namespace App\Form\Type;

use App\Form\DataTransformer\Base64ToCsvDatasetTransformer;
use App\Form\Model\Csv\Base64CsvFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckedBase64CsvUserImportFileType extends AbstractType
{
    /**
     * @var Base64ToCsvDatasetTransformer
     */
    private $transformer;

    public function __construct(Base64ToCsvDatasetTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('base64Content', HiddenType::class, [
                'invalid_message' => 'Import was not valid.',
            ])
        ;

        $builder->get('base64Content')
            ->addModelTransformer($this->transformer);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Base64CsvFile $base64CsvFile */
            $base64CsvFile = $event->getData();
            $form = $event->getForm();

            $form->add('checked', CheckboxType::class, [
                'required' => false,
                'label' => $base64CsvFile ? $base64CsvFile->getFilename() : '',
            ]);
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Base64CsvFile::class
        ]);
    }
}