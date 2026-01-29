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

namespace App\Utils\Tree;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class Tree
{
    private array $data = [];

    private array $highlightedIds = [];

    private array $options = [];

    private ?string $checkboxSelector = null;

    public function __construct(TreeMode $mode)
    {
        $this->options['plugins'] = ['wholerow'];

        if ($mode === TreeMode::CHECKBOXES) {
            $this->options['checkbox'] = [
                'keep_selected_style' => false,
                'three_state' => false,
            ];
            $this->options['plugins'][] = ['checkbox'];
        }
    }

    public function createView(): array
    {
        return [
            'data' => $this->mapTreeData($this->getData()),
            'options' => $this->options,
            'checkboxSelector' => $this->checkboxSelector,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getHighlightedIds(): array
    {
        return $this->highlightedIds;
    }

    public function setHighlightedIds(array $highlightedIds): Tree
    {
        $this->highlightedIds = $highlightedIds;
        return $this;
    }

    public function getCheckboxSelector(): ?string
    {
        return $this->checkboxSelector;
    }

    /**
     * Selector to sync hidden form checkboxes
     */
    public function setCheckboxSelector(string $checkboxSelector): self
    {
        $this->checkboxSelector = $checkboxSelector;
        return $this;
    }

    private function mapTreeData(array $nodes): array
    {
        $mapNode = function (array $node) use (&$mapNode): array {
            $children = $node['children'] ?? [];
            $isHighlighted = in_array($node['item_id'], $this->getHighlightedIds());

            return [
                'id' => $node['item_id'],
                'text' => $node['title'],
                'a_attr' => ['class' => $isHighlighted ? 'uk-text-bold' : ''],
                'state' => ['selected' => $isHighlighted],
                'children' => is_array($children) ? array_map($mapNode, $children) : [],
            ];
        };

        return array_map($mapNode, $nodes);
    }
}
