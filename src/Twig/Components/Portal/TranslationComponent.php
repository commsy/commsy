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

namespace App\Twig\Components\Portal;

use App\Entity\Account;
use App\Entity\Translation;
use App\Form\Type\TranslationType;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Entity\Translation as TranslationEntity;

#[AsLiveComponent]
final class TranslationComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public int $portalId;

    #[LiveProp(writable: true)]
    public ?TranslationEntity $currentTranslation = null;

    public function __construct(
        private readonly TranslationRepository $translationRepository,
        private readonly Security $security,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(TranslationType::class, null, [
            'portalId' => $this->portalId,
        ]);
    }

    #[PostHydrate]
    public function checkAccess(): void
    {
        if ($this->currentTranslation) {
            $portalId = $this->currentTranslation->getContextId();

            $account = $this->security->getUser();
            if ($account instanceof Account) {
                if ($account->getContextId() === $portalId) {
                    return;
                }
            }

            throw new AccessDeniedHttpException();
        }
    }

    // Editing portal-wide translations is moderator work. The hydration check
    // below establishes that actor and translation belong to the same portal;
    // this establishes the role. The two are complementary, not redundant.
    #[LiveAction]
    #[IsGranted('PORTAL_MODERATOR')]
    public function select(): void
    {
        $translationId = $this->formValues['translation'];
        $translation = $this->translationRepository->find($translationId);
        $this->currentTranslation = $translation;

        $this->formValues['translationDe'] = $translation?->getTranslationDe();
        $this->formValues['translationEn'] = $translation?->getTranslationEn();
    }

    #[LiveAction]
    #[IsGranted('PORTAL_MODERATOR')]
    public function save(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        $data = $this->getForm()->getData();

        /** @var Translation $translation */
        $translation = $data['translation'];
        $translation->setTranslationDe($data['translationDe']);
        $translation->setTranslationEn($data['translationEn']);

        $entityManager->persist($translation);
        $entityManager->flush();
    }
}
