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
    public function __construct(private readonly TranslatorInterface $translator, private readonly FileToUserImportTransformer $transformer)
    {
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
                'label' => 'Files',
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
                'choice_label' => function (AuthSource $authSource) {
                    if ($authSource->isDefault()) {
                        return $authSource->getTitle().' ('.$this->translator->trans('Default Source', [],
                            'portal').')';
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
                'translation_domain' => 'portal',
            ]);
    }
}
