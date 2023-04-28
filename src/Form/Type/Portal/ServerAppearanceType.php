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

namespace App\Form\Type\Portal;

use App\Entity\Server;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ServerAppearanceType extends AbstractType
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('logoImageFile', VichImageType::class, [
                'download_uri' => false,
                'image_uri' => function (Server $server) {
                    if ($server->getLogoImageFile()) {
                        return $this->router->generate('app_file_serverlogo');
                    }

                    return '';
                },
                'required' => false,
                'label' => 'Logo',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Server::class,
        ]);
    }
}
