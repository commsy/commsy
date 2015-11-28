<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class MaterialTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param cs_material_item $materialItem
     * @return array
     */
    public function transform($materialItem)
    {
        $materialData = array();

        if ($materialItem) {
            $materialData['title'] = $materialItem->getTitle();
            // $materialData['language'] = $materialItem->getLanguage();

            // if ($materialItem->checkNewMembersAlways()) {
            //     $materialData['access_check'] = 'always';
            // } else if ($materialItem->checkNewMembersNever()) {
            //     $materialData['access_check'] = 'never';
            // } else if ($materialItem->checkNewMembersSometimes()) {
            //     $materialData['access_check'] = 'sometimes';
            // } else if ($materialItem->checkNewMembersWithCode()) {
            //     $materialData['access_check'] = 'withcode';
            // }

            $materialData['description'] = $materialItem->getDescription();
            $materialData['permission'] = $materialItem->isPrivateEditing();

            if (get_class($materialItem) != 'cs_section_item') {

                if ($materialItem->getBibKind() != 'none') {
                    $materialData['biblio_select'] = 'Biblio'.ucfirst($materialItem->getBibKind()).'Type';
                }

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
                $materialData['biblio_sub']['url_date'] = new \DateTime($materialItem->getURLDate());
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

            }

        }

        return $materialData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $materialObject
     * @param array $materialData
     * @return cs_material_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($materialObject, $materialData)
    {
        $materialObject->setTitle($materialData['title']);
        $materialObject->setDescription($materialData['description']);
        $materialObject->setPrivateEditing($materialData['permission']);

        if (get_class($materialObject) != 'cs_section_item') {
            var_dump($materialData['biblio_sub']);
            // bibliographic data
            if ($materialData['biblio_sub']) {
                $bibData = $materialData['biblio_sub'];

                $this->setBibliographic($materialData['biblio_sub'], $materialObject);

                // bib_kind
                // BiblioPlainType
                if (isset($materialData['biblio_select'])) {
                    $type = $materialData['biblio_select'];
                    $type = str_replace("Biblio", "", $type);
                    $type = str_replace("Type", "", $type);

                    if (!empty($type)) {
                        $materialObject->setBibKind(strtolower($type));    
                    } else {
                        $materialObject->setBibKind('none');
                    }
                } else {
                    $materialObject->setBibKind('none');
                }
                

                $materialObject->save();
            }
        }
        
        
        return $materialObject;
    }

    private function setBibliographic($form_data, $item) {
        $bibFields = array( 'author',
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
                            'foto_date');
        
        foreach ($bibFields as $key => $value) {
            $form_data[$value] = isset($form_data[$value]) ? $form_data[$value] : '';
            if($value == 'url_date' && $form_data[$value] != '') {
                $form_data[$value] = $form_data[$value]->format('Y-m-d');
            }
        }

        isset($form_data['value']) ? $form_data['value'] : '';
        $config = array(
            array(  'get'       => 'getAuthor',
                    'set'       => 'setAuthor',
                    'value'     => $form_data['author']),
            array(  'get'       => 'getPublishingDate',
                    'set'       => 'setPublishingDate',
                    'value'     => $form_data['publishing_date']),
            array(  'get'       => 'getBibliographicValues',
                    'set'       => 'setBibliographicValues',
                    'value'     => $form_data['common']),
            array(  'get'       => 'getBibKind',
                    'set'       => 'setBibKind',
                    'value'     => $form_data['bib_kind']),
            array(  'get'       => 'getPublisher',
                    'set'       => 'setPublisher',
                    'value'     => $form_data['publisher']),
            array(  'get'       => 'getAddress',
                    'set'       => 'setAddress',
                    'value'     => $form_data['address']),
            array(  'get'       => 'getEdition',
                    'set'       => 'setEdition',
                    'value'     => $form_data['edition']),
            array(  'get'       => 'getSeries',
                    'set'       => 'setSeries',
                    'value'     => $form_data['series']),
            array(  'get'       => 'getVolume',
                    'set'       => 'setVolume',
                    'value'     => $form_data['volume']),
            array(  'get'       => 'getISBN',
                    'set'       => 'setISBN',
                    'value'     => $form_data['isbn']),
            array(  'get'       => 'getURL',
                    'set'       => 'setURL',
                    'value'     => $form_data['url']),
            array(  'get'       => 'getURLDate',
                    'set'       => 'setURLDate',
                    'value'     => $form_data['url_date']),
            array(  'get'       => 'getEditor',
                    'set'       => 'setEditor',
                    'value'     => $form_data['editor']),
            array(  'get'       => 'getBooktitle',
                    'set'       => 'setBooktitle',
                    'value'     => $form_data['booktitle']),
            array(  'get'       => 'getISSN',
                    'set'       => 'setISSN',
                    'value'     => $form_data['issn']),
            array(  'get'       => 'getPages',
                    'set'       => 'setPages',
                    'value'     => $form_data['pages']),
            array(  'get'       => 'getJournal',
                    'set'       => 'setJournal',
                    'value'     => $form_data['journal']),
            array(  'get'       => 'getIssue',
                    'set'       => 'setIssue',
                    'value'     => $form_data['issue']),
            array(  'get'       => 'getThesisKind',
                    'set'       => 'setThesisKind',
                    'value'     => $form_data['thesis_kind']),
            array(  'get'       => 'getUniversity',
                    'set'       => 'setUniversity',
                    'value'     => $form_data['university']),
            array(  'get'       => 'getFaculty',
                    'set'       => 'setFaculty',
                    'value'     => $form_data['faculty']),
            array(  'get'       => 'getFotoCopyright',
                    'set'       => 'setFotoCopyright',
                    'value'     => $form_data['foto_copyright']),
            array(  'get'       => 'getFotoReason',
                    'set'       => 'setFotoReason',
                    'value'     => $form_data['foto_reason']),
            array(  'get'       => 'getFotoDate',
                    'set'       => 'setFotoDate',
                    'value'     => $form_data['foto_date'])
        );

        foreach($config as $method => $detail) {
            if($detail['value'] != call_user_func_array(array($item, $detail['get']), array())) {
                call_user_func_array(array($item, $detail['set']), array($detail['value']));
            }
        }
    }
}