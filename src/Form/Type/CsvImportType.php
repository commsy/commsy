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
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class CsvImportType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TranslatorInterface $translator */
        $translator = $options['translator'];

        $uploadErrorMessage = $translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $translator->trans('upload error', [], 'error');

        $builder
            ->add('upload', FileType::class, [
                'attr' => [
                    'data-uk-csupload' => '{"path": "' . $options['uploadUrl'] . '", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}',
                    'accept' => 'text/csv',
                ],
                'required' => true,
                'multiple' => true,
                'label' => 'Files'
            ])
            ->add('base64', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedBase64CsvUserImportFileType::class,
                'label' => false,
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
                    /** @var Portal $portal */
                    $portal = $options['portal'];
                    $extras = $portal->getExtras();

                    /** @var TranslatorInterface $translator */
                    $translator = $options['translator'];

                    if (isset($extras['DEFAULT_AUTH'])) {
                        $defaultAuthSource = $extras['DEFAULT_AUTH'];

                        if ($authSource->getItemId() === (int) $defaultAuthSource) {
                            return $authSource->getTitle() . ' (' . $translator->trans('Default Source', [], 'portal') . ')';
                        }
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
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['uploadUrl', 'portal', 'translator'])
            ->setDefaults([
                'translation_domain' => 'portal'
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'csv_import';
    }

}