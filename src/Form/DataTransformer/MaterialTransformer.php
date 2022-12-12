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

namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_material_item;
use DateTime;

class MaterialTransformer extends AbstractTransformer
{
    protected $entity = 'material';

    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_material_item object to an array.
     *
     * @param cs_material_item $materialItem
     *
     * @return array
     */
    public function transform($materialItem)
    {
        $materialData = [];

        if ($materialItem) {
            $materialData['title'] = html_entity_decode($materialItem->getTitle());
            $materialData['draft'] = $materialItem->isDraft();
            $materialData['description'] = $materialItem->getDescription();
            $materialData['permission'] = $materialItem->isPrivateEditing();

            if ($materialItem instanceof cs_material_item) {
                $materialData['editor_switch'] = $materialItem->getEtherpadEditor() > 0;

                if ('none' != $materialItem->getBibKind()) {
                    $bibKind = $materialItem->getBibKind();
                    // Bugfix: add backwards compatibility for entries from databases migrated from CommSy < 9
                    if ('document' == $bibKind || 'docmanagement' == $bibKind) {
                        $bibKind = 'DocManagement';
                    }
                    $materialData['biblio_select'] = 'Biblio'.ucfirst($bibKind).'Type';
                }

                /* @var cs_material_item $materialItem */
                $materialData['biblio_sub']['author'] = $materialItem->getAuthor();
                $materialData['biblio_sub']['publishing_date'] = $materialItem->getPublishingDate();
                $materialData['biblio_sub']['common'] = $materialItem->getBibliographicValues();
                $materialData['biblio_sub']['publisher'] = $materialItem->getPublisher();
                $materialData['biblio_sub']['address'] = $materialItem->getAddress();
                $materialData['biblio_sub']['edition'] = $materialItem->getEdition();
                $materialData['biblio_sub']['series'] = $materialItem->getSeries();
                $materialData['biblio_sub']['volume'] = $materialItem->getVolume();
                $materialData['biblio_sub']['isbn'] = $materialItem->getISBN();
                $materialData['biblio_sub']['url'] = $materialItem->getURL();
                $materialData['biblio_sub']['url_date'] = $materialItem->getURLDate();
                $materialData['biblio_sub']['editor'] = $materialItem->getEditor();
                $materialData['biblio_sub']['booktitle'] = $materialItem->getBooktitle();
                $materialData['biblio_sub']['issn'] = $materialItem->getISSN();
                $materialData['biblio_sub']['pages'] = $materialItem->getPages();
                $materialData['biblio_sub']['journal'] = $materialItem->getJournal();
                $materialData['biblio_sub']['issue'] = $materialItem->getIssue();
                $materialData['biblio_sub']['thesis_kind'] = $materialItem->getThesisKind();
                $materialData['biblio_sub']['university'] = $materialItem->getUniversity();
                $materialData['biblio_sub']['faculty'] = $materialItem->getFaculty();
                $materialData['biblio_sub']['foto_copyright'] = $materialItem->getFotoCopyright();
                $materialData['biblio_sub']['foto_reason'] = $materialItem->getFotoReason();
                $materialData['biblio_sub']['foto_date'] = $materialItem->getFotoDate();
                $materialData['biblio_sub']['document_editor'] = $materialItem->getDocumentEditor();
                $materialData['biblio_sub']['document_maintainer'] = $materialItem->getDocumentMaintainer();
                $materialData['biblio_sub']['document_release_number'] = $materialItem->getDocumentReleaseNumber();
                $materialData['biblio_sub']['document_release_date'] = $materialItem->getDocumentReleaseDate();

                $materialData['sections'] = [];
                foreach ($materialItem->getSectionList()->to_array() as $id => $item) {
                    $materialData['sections'][$item->getItemID()] = $item->getTitle();
                }

                // external viewer
                if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                    $materialData['external_viewer_enabled'] = true;
                    $materialData['external_viewer'] = $materialItem->getExternalViewerString();
                } else {
                    $materialData['external_viewer_enabled'] = false;
                }

                $materialData['license_id'] = $materialItem->getLicenseId();
            }

            if ($materialItem->isNotActivated()) {
                $materialData['hidden'] = true;

                $activating_date = $materialItem->getActivatingDate();
                if (!stristr($activating_date, '9999')) {
                    $datetime = new DateTime($activating_date);
                    $materialData['hiddendate']['date'] = $datetime;
                    $materialData['hiddendate']['time'] = $datetime;
                }
            }
        }

        return $materialData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $materialObject
     * @param array  $materialData
     *
     * @return cs_material_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($materialObject, $materialData)
    {
        $materialObject->setTitle($materialData['title']);
        $materialObject->setDescription($materialData['description']);

        if (array_key_exists('editor_switch', $materialData)) {
            if ($materialData['editor_switch']) {
                $materialObject->setEtherpadEditor('1');
            }
        }

        if ($materialData['permission']) {
            $materialObject->setPrivateEditing('0');
        } else {
            $materialObject->setPrivateEditing('1');
        }

        if ('cs_section_item' != $materialObject::class) {
            // bibliographic data
            if ($materialData['biblio_sub']) {
                $bibData = $materialData['biblio_sub'];

                $this->setBibliographic($materialData['biblio_sub'], $materialObject);

                // bib_kind
                // BiblioPlainType
                if (isset($materialData['biblio_select'])) {
                    $type = $materialData['biblio_select'];
                    $type = str_replace('Biblio', '', $type);
                    $type = str_replace('Type', '', $type);

                    if (!empty($type)) {
                        $materialObject->setBibKind(strtolower($type));
                    } else {
                        $materialObject->setBibKind('none');
                    }
                } else {
                    $materialObject->setBibKind('none');
                }
            }

            // external viewer
            if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                if (!empty(trim($materialData['external_viewer']))) {
                    $userIds = explode(' ', $materialData['external_viewer']);
                    $materialObject->setExternalViewerAccounts($userIds);
                } else {
                    $materialObject->unsetExternalViewerAccounts();
                }
            }
        }

        if (isset($materialData['hidden']) && $materialData['hidden']) {
            if (isset($materialData['hiddendate']['date']) && $materialData['hiddendate']['date']) {
                // add validdate to validdate
                $datetime = $materialData['hiddendate']['date'];
                if ($materialData['hiddendate']['time']) {
                    $time = explode(':', $materialData['hiddendate']['time']->format('H:i'));
                    $datetime->setTime($time[0], $time[1]);
                }
                $materialObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
            } else {
                $materialObject->setActivationDate('9999-00-00 00:00:00');
            }
        } else {
            if ($materialObject->isNotActivated()) {
                $materialObject->setActivationDate(null);
            }
        }

        if ('cs_section_item' != $materialObject::class) {
            if (array_key_exists('license_id', $materialData)) {
                $materialObject->setLicenseId($materialData['license_id']);
            } else {
                $materialObject->setLicenseId(null);
            }
        }

        // sections
        if (isset($materialData['sectionOrder'])) {
            $section_manager = $this->legacyEnvironment->getSectionManager();
            $newSectionOrder = explode(',', $materialData['sectionOrder']);
            foreach ($newSectionOrder as $counter => $id) {
                $section_item = $section_manager->getItem($id);
                if (!empty($section_item)) {
                    $section_item->setNumber($counter + 1);
                    $section_item->save();
                }
            }
        }

        return $materialObject;
    }

    private function setBibliographic($form_data, $item)
    {
        $bibFields = [
            'author',
            'publishing_date',
            'common',
            'bib_kind',
            'publisher',
            'address',
            'edition',
            'series',
            'volume',
            'isbn',
            'url',
            'url_date',
            'editor',
            'booktitle',
            'issn',
            'pages',
            'journal',
            'issue',
            'thesis_kind',
            'university',
            'faculty',
            'foto_copyright',
            'foto_reason',
            'foto_date',
            'document_editor',
            'document_maintainer',
            'document_release_number',
            'document_release_date',
];

        $converter = $this->legacyEnvironment->getTextConverter();

        foreach ($bibFields as $key => $value) {
            $form_data[$value] = isset($form_data[$value]) ? $converter->sanitizeFullHTML($form_data[$value]) : '';
            if ('url_date' == $value && '' != $form_data[$value]) {
                // $form_data[$value] = new \DateTime($form_data[$value]);
                // $form_data[$value] = $form_data[$value]->format('Y-m-d');
            }
        }

        $form_data['value'] ?? '';
        $config = [
            ['get' => 'getAuthor', 'set' => 'setAuthor', 'value' => $form_data['author']],
            ['get' => 'getPublishingDate', 'set' => 'setPublishingDate', 'value' => $form_data['publishing_date']],
            ['get' => 'getBibliographicValues', 'set' => 'setBibliographicValues', 'value' => $form_data['common']],
            ['get' => 'getBibKind', 'set' => 'setBibKind', 'value' => $form_data['bib_kind']],
            ['get' => 'getPublisher', 'set' => 'setPublisher', 'value' => $form_data['publisher']],
            ['get' => 'getAddress', 'set' => 'setAddress', 'value' => $form_data['address']],
            ['get' => 'getEdition', 'set' => 'setEdition', 'value' => $form_data['edition']],
            ['get' => 'getSeries', 'set' => 'setSeries', 'value' => $form_data['series']],
            ['get' => 'getVolume', 'set' => 'setVolume', 'value' => $form_data['volume']],
            ['get' => 'getISBN', 'set' => 'setISBN', 'value' => $form_data['isbn']],
            ['get' => 'getURL', 'set' => 'setURL', 'value' => $form_data['url']],
            ['get' => 'getURLDate', 'set' => 'setURLDate', 'value' => $form_data['url_date']],
            ['get' => 'getEditor', 'set' => 'setEditor', 'value' => $form_data['editor']],
            ['get' => 'getBooktitle', 'set' => 'setBooktitle', 'value' => $form_data['booktitle']],
            ['get' => 'getISSN', 'set' => 'setISSN', 'value' => $form_data['issn']],
            ['get' => 'getPages', 'set' => 'setPages', 'value' => $form_data['pages']],
            ['get' => 'getJournal', 'set' => 'setJournal', 'value' => $form_data['journal']],
            ['get' => 'getIssue', 'set' => 'setIssue', 'value' => $form_data['issue']],
            ['get' => 'getThesisKind', 'set' => 'setThesisKind', 'value' => $form_data['thesis_kind']],
            ['get' => 'getUniversity', 'set' => 'setUniversity', 'value' => $form_data['university']],
            ['get' => 'getFaculty', 'set' => 'setFaculty', 'value' => $form_data['faculty']],
            ['get' => 'getFotoCopyright', 'set' => 'setFotoCopyright', 'value' => $form_data['foto_copyright']],
            ['get' => 'getFotoReason', 'set' => 'setFotoReason', 'value' => $form_data['foto_reason']],
            ['get' => 'getFotoDate', 'set' => 'setFotoDate', 'value' => $form_data['foto_date']],
            ['get' => 'getDocumentEditor', 'set' => 'setDocumentEditor', 'value' => $form_data['document_editor']],
            ['get' => 'getDocumentMaintainer', 'set' => 'setDocumentMaintainer', 'value' => $form_data['document_maintainer']],
            ['get' => 'getDocumentReleaseNumber', 'set' => 'setDocumentReleaseNumber', 'value' => $form_data['document_release_number']],
            ['get' => 'getDocumentReleaseDate', 'set' => 'setDocumentReleaseDate', 'value' => $form_data['document_release_date']],
        ];

        foreach ($config as $method => $detail) {
            if ($detail['value'] != call_user_func_array([$item, $detail['get']], [])) {
                call_user_func_array([$item, $detail['set']], [$detail['value']]);
            }
        }
    }
}
