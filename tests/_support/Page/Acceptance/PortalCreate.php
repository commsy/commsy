<?php
namespace App\Tests\Page\Acceptance;

use App\Tests\AcceptanceTester;

class PortalCreate
{
    // include url of current page
    public static $URL = '?cid=99&mod=configuration&fct=preferences&iid=NEW';

    public static $titleField = 'input[name="title"]';
    public static $descriptionField = 'textarea[name="description"]';
    public static $urlField = 'input[name="url"]';

    public static $createButton = 'form[name="f"] input[type="submit"]';

    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    public function create(string $title, string $description = '')
    {
        $I = $this->tester;

        $I->amOnPage(self::$URL);

        $I->fillField(self::$titleField, $title);
        $I->fillField(self::$descriptionField, $description);

        $I->click(self::$createButton);

        return $this;
    }
}
