<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\Materials;
use PHPUnit\Framework\TestCase;

/**
 * The citation is the one part of `extras` that is content. What the export
 * hands on of it has to be everything that holds something, and nothing else.
 */
class MaterialsBibliographicTest extends TestCase
{
    public function testStructuredFieldsAreHandedOnUnderTheirKeyWithoutThePrefix(): void
    {
        $citation = $this->citation([
            'BIB_KIND' => 'book',
            'BIBLIOGRAPHIC' => 'Berlin: Springer.',
            'BIB_PUBLISHER' => 'Springer',
            'BIB_ADDRESS' => 'Berlin',
            'BIB_URL_DATE' => '2024-01-02',
            'BIB_THESIS_KIND' => 'Dissertation',
            'BIB_BOOKTITLE' => 'Sammelband',
        ]);

        $this->assertSame('book', $citation['kind']);
        $this->assertSame('Berlin: Springer.', $citation['text']);
        $this->assertSame('Springer', $citation['publisher']);
        $this->assertSame('Berlin', $citation['address']);
        $this->assertSame('2024-01-02', $citation['urlDate']);
        $this->assertSame('Dissertation', $citation['thesisKind']);
        $this->assertSame('Sammelband', $citation['booktitle']);
    }

    /**
     * An entry that was changed from one kind to another keeps the fields of the
     * old one in its row. They are handed on: whether they matter is for the
     * importer to say.
     */
    public function testFieldsOfAnotherKindAreKept(): void
    {
        $citation = $this->citation(['BIB_KIND' => 'article', 'BIB_ISBN' => '978-3-16-148410-0']);

        $this->assertSame('978-3-16-148410-0', $citation['isbn']);
    }

    /**
     * Kinds of an older vocabulary — `incollection`, `common` — are no longer
     * offered by the interface, but their rows exist.
     */
    public function testAKindTheInterfaceNoLongerOffersStillCarriesItsFields(): void
    {
        $citation = $this->citation(['BIB_KIND' => 'incollection', 'BIB_PAGES' => '30 - 32', 'BIB_JOURNAL' => 'Pädagogik']);

        $this->assertSame('incollection', $citation['kind']);
        $this->assertSame('30 - 32', $citation['pages']);
        $this->assertSame('Pädagogik', $citation['journal']);
    }

    public function testEmptyFieldsAreLeftOut(): void
    {
        $citation = $this->citation([
            'BIB_KIND' => 'book',
            'BIB_PUBLISHER' => '',
            'BIB_ADDRESS' => '   ',
            'BIB_EDITION' => '3. Auflage',
        ]);

        $this->assertArrayNotHasKey('publisher', $citation);
        $this->assertArrayNotHasKey('address', $citation);
        $this->assertSame('3. Auflage', $citation['edition']);
    }

    /**
     * Handed on as stored: the export does not tidy what the room shows.
     */
    public function testAValueIsNotRewritten(): void
    {
        $citation = $this->citation(['BIB_KIND' => 'book', 'BIB_PUBLISHER' => "Erlbaum.\n "]);

        $this->assertSame("Erlbaum.\n ", $citation['publisher']);
    }

    public function testNothingButBookkeepingMeansNoCitation(): void
    {
        $this->assertNull($this->citationOrNull(['BIB_KIND' => 'none', 'BIBLIOGRAPHIC' => '', 'COPY' => ['ITEM_ID' => 5]]));
        $this->assertNull($this->citationOrNull(null));
    }

    /**
     * Nothing chosen and nothing written, yet a field is filled — what is left of
     * an earlier kind. Dropping it would lose the value.
     */
    public function testALeftOverFieldStillMakesACitation(): void
    {
        $citation = $this->citation(['BIB_KIND' => 'none', 'BIBLIOGRAPHIC' => '', 'BIB_PUBLISHER' => 'Springer']);

        $this->assertNull($citation['kind']);
        $this->assertNull($citation['text']);
        $this->assertSame('Springer', $citation['publisher']);
    }

    /**
     * What is not a citation field is not exported, however it is named.
     */
    public function testOtherExtrasStayOut(): void
    {
        $citation = $this->citation([
            'BIB_KIND' => 'book',
            'BIB_SOMETHING_NEW' => 'x',
            'COPY' => ['ITEM_ID' => 5],
            'etherpad_id' => 'g.abc$1',
        ]);

        $this->assertSame(['kind', 'text'], array_keys($citation));
    }

    /**
     * The list of fields is a copy of what legacy knows. A key added there and
     * forgotten here would vanish from every export without anyone noticing.
     */
    public function testEveryCitationKeyLegacyKnowsIsExported(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../legacy/classes/cs_material_item.php');
        preg_match_all('/BIB_[A-Z_]+/', $source, $matches);

        $keys = array_values(array_diff(array_unique($matches[0]), ['BIB_KIND']));
        $this->assertNotEmpty($keys, 'the scan found nothing — the test would pass for the wrong reason');

        $citation = $this->citation(array_fill_keys($keys, 'x'));

        // The kind and the text are not among them: two keys, however many fields.
        $this->assertCount(count($keys) + 2, $citation, sprintf(
            'legacy knows %d citation keys, the export carries %d of them',
            count($keys),
            count($citation) - 2
        ));
    }

    /**
     * @param array<string, mixed>|null $extras
     *
     * @return array<string, string|null>
     */
    private function citation(?array $extras): array
    {
        $citation = $this->citationOrNull($extras);
        $this->assertNotNull($citation);

        return $citation;
    }

    /**
     * @param array<string, mixed>|null $extras
     *
     * @return array<string, string|null>|null
     */
    private function citationOrNull(?array $extras): ?array
    {
        return (new Materials())->setExtras($extras)->getBibliographic();
    }
}
