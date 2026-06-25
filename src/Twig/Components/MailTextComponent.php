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
use App\Mail\Text\MailTextCatalog;
use App\Mail\Text\MailTextRenderer;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Portal mail-text override editor.
 *
 * Reads the editable default from the catalog (named-token format), lets the admin insert
 * placeholders as plain tokens, and stores the override in the new named-token format. The
 * legacy cs_translator/.dat is no longer involved; the room type is resolved at send time
 * from the {roomTypeName} placeholder, never picked by the admin.
 */
#[AsLiveComponent('mail_text')]
final class MailTextComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public MailText $mailText;

    #[LiveProp]
    public Portal $portal;

    public function __construct(
        private readonly MailTextCatalog $catalog,
        private readonly MailTextRenderer $renderer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(MailTextType::class, $this->mailText);
    }

    #[LiveAction]
    public function select(): void
    {
        $selected = $this->selectedMessageId();
        $overrides = $this->portal->getEmailTextArray();

        $this->formValues['contentGerman'] = $overrides[$selected]['de'] ?? $this->renderer->templateFor($selected, 'de');
        $this->formValues['contentEnglish'] = $overrides[$selected]['en'] ?? $this->renderer->templateFor($selected, 'en');

        $this->dispatchBrowserEvent('editor:get-value');
    }

    #[LiveAction]
    public function resetContent(#[LiveArg] string $lang): void
    {
        if ('de' !== $lang && 'en' !== $lang) {
            throw new LogicException('lang must be either "de" or "en"');
        }

        $template = $this->renderer->templateFor($this->selectedMessageId(), $lang);
        if ('de' === $lang) {
            $this->formValues['contentGerman'] = $template;
        } else {
            $this->formValues['contentEnglish'] = $template;
        }

        $this->dispatchBrowserEvent('editor:get-value');
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        /** @var MailText $mailText */
        $mailText = $this->form->getData();

        $this->portal->setEmailText($mailText->getMailText(), [
            'de' => $mailText->getContentGerman(),
            'en' => $mailText->getContentEnglish(),
        ]);

        // mark the portal's stored overrides as the new named-token format
        $extras = $this->portal->getExtras() ?? [];
        $extras['MAIL_TEXT_ARRAY_VERSION'] = 2;
        $this->portal->setExtras($extras);

        $entityManager->persist($this->portal);
        $entityManager->flush();
    }

    /**
     * Placeholders the admin may insert for the selected mail text, as {token, label} pairs.
     *
     * @return list<array{token: string, label: string}>
     */
    public function availablePlaceholders(): array
    {
        $definition = $this->catalog->byLegacyId($this->selectedMessageId());
        if (null === $definition) {
            return [];
        }

        $locale = $this->translator->getLocale();
        $placeholders = [];
        foreach ($definition->availablePlaceholders() as $placeholder) {
            $placeholders[] = ['token' => $placeholder->token(), 'label' => $placeholder->label($locale)];
        }

        return $placeholders;
    }

    /**
     * Live preview of the given language's content, rendered with example values.
     */
    public function preview(string $lang): string
    {
        $selected = $this->selectedMessageId();
        $definition = $this->catalog->byLegacyId($selected);
        if (null === $definition) {
            return '';
        }

        $content = 'de' === $lang ? ($this->formValues['contentGerman'] ?? '') : ($this->formValues['contentEnglish'] ?? '');
        if (!is_string($content) || '' === $content) {
            return '';
        }

        $sampleValues = array_map(static fn ($placeholder) => $placeholder->sample($lang), $definition->positionalParams);

        return $this->renderer->render($selected, 'project', $lang, $sampleValues, [$selected => [$lang => $content]]);
    }

    private function selectedMessageId(): string
    {
        $selected = $this->formValues['mailText'] ?? null;

        return is_string($selected) ? $selected : '';
    }
}
