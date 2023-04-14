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

namespace App\Twig\Components;

use App\Entity\Portal;
use App\Form\Model\MailText;
use App\Form\Type\Portal\MailTextType;
use App\Services\LegacyEnvironment;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('mail_text')]
final class MailTextComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'data')]
    public MailText $mailText;

    #[LiveProp]
    public Portal $portal;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(MailTextType::class, $this->mailText);
    }

    public function __invoke(LegacyEnvironment $environment): void
    {
        $legacyEnvironment = $environment->getEnvironment();
        $translator = $legacyEnvironment->getTranslationObject();

        $translator->setEmailTextArray([]);

        $portalTextArray = $this->portal->getEmailTextArray();

        $mailText = $this->formValues['mailText'];
        $contentGerman = $portalTextArray[$mailText]['de'] ?? $translator->getEmailMessageInLang('de', $mailText);
        $contentEnglish = $portalTextArray[$mailText]['en'] ?? $translator->getEmailMessageInLang('en', $mailText);

        $this->formValues['contentGerman'] = $contentGerman;
        $this->formValues['contentEnglish'] = $contentEnglish;
    }

    #[LiveAction]
    public function resetContent(
        #[LiveArg] string $lang,
        LegacyEnvironment $environment
    ): void
    {
        if ($lang !== 'de' && $lang !== 'en') {
            throw new LogicException('lang must be either "de" or "en"');
        }

        $legacyEnvironment = $environment->getEnvironment();
        $translator = $legacyEnvironment->getTranslationObject();

        $translator->setEmailTextArray([]);

        if ($lang === 'de') {
            $defaultContent = $translator->getEmailMessageInLang($lang, $this->formValues['mailText']);
            $this->formValues['contentGerman'] = $defaultContent;
        } else {
            $defaultContent = $translator->getEmailMessageInLang($lang, $this->formValues['mailText']);
            $this->formValues['contentEnglish'] = $defaultContent;
        }
    }
}
