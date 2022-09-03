<?php
namespace Tests\Support\Page\Acceptance;

use Tests\Support\AcceptanceTester;

class RoomCreate
{
    // include url of current page
//    public static $URL_PART1 = '/room/';
//    public static $URL_PART2 = '/all/create';
    public static $URL_PART1 = '?cid=';
    PUBLIC STATIC $URL_PART2 = '&mod=project&fct=edit&iid=NEW';

    public static $titleField = 'input[name="context[title]"]';
    public static $typeField = 'input[name="context[type_select]"]';
    public static $descriptionField = 'textarea[name="context[room_description]"]';
    public static $saveButton = 'button[name="context[save]"]';

    public static $legacyTitleField = 'input[name="title"]';
    public static $legacyDescriptionField = 'textarea[name="description"]';
    public static $legacySaveButton = 'input[type="submit"]';

    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    public static function route(int $contextId)
    {
        return static::$URL_PART1 . $contextId . static::$URL_PART2;
    }

    public function createProjectRoom(int $portalId, string $title, string $description = '')
    {
        $I = $this->tester;

        $I->amOnPage(self::route((string) $portalId));

        $I->fillField(self::$titleField, $title);
        $I->selectOption(self::$typeField, 'project');
        $I->fillField(self::$descriptionField, $description);

        $I->click(self::$saveButton);

        return $this;
    }

    public function createProjectRoomLegacy(int $portalId, string $title, string $description = '')
    {
        $I = $this->tester;

        $I->amOnPage(self::route((string) $portalId));

        $I->fillField(self::$legacyTitleField, $title);
        $I->fillField(self::$legacyDescriptionField, $description);

        $I->click(self::$legacySaveButton);

        return $this;
    }
}
