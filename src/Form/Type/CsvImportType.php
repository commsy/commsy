<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.07.18
 * Time: 11:29
 */

namespace App\Form\Type;


use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Form\DataTransformer\FileToUserImportTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvImportType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var FileToUserImportTransformer
     */
    private FileToUserImportTransformer $transformer;

    public function __construct(TranslatorInterface $translator, FileToUserImportTransformer $transformer)
    {
        $this->translator = $translator;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('csv', FileType::class, [
                'attr' => [
                    'accept' => 'text/csv',
                ],
                'required' => true,
                'label' => 'Files'
            ])
            ->add('auth_sources', EntityType::class, [
                'class' => AuthSource::class,
                'label' => 'authSource',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    /** @var Portal $portal */
                    $portal = $options['portal'];

                    return $er->createQueryBuilder('a')
                        ->where('a.portal = :portal')
                        ->orderBy('a.title')
                        ->setParameter('portal', $portal);
                },
                'choice_label' => function (AuthSource $authSource) use ($options) {
                    if ($authSource->isDefault()) {
                        return $authSource->getTitle() . ' (' . $this->translator->trans('Default Source', [],
                                'portal') . ')';
                    }

                    return $authSource->getTitle();
                },
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ]);

        $builder->get('csv')
            ->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['portal'])
            ->setDefaults([
                'translation_domain' => 'portal'
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'csv_import';
    }

}