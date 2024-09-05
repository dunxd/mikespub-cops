<?php
/**
 * COPS (Calibre OPDS PHP Server) test file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Tests;

require_once __DIR__ . '/config_test.php';
use PHPUnit\Framework\TestCase;
use SebLucas\Cops\Calibre\Database;
use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Input\Route;
use SebLucas\Cops\Output\Format;
use SebLucas\Cops\Output\HtmlRenderer;
use SebLucas\Cops\Output\JsonRenderer;
use SebLucas\Cops\Language\Translation;
use DOMDocument;
use Exception;

class BaseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        Database::clearDb();
    }

    public function testRouteQuery(): void
    {
        $this->assertEquals("?db=0", Route::query("", ["db" => "0"]));
        $this->assertEquals("?key=value&db=0", Route::query("key=value", ["db" => "0"]));
        $this->assertEquals("?key=value&db=0", Route::query("key=value&otherKey", ["db" => "0"]));
        $this->assertEquals("?key=value&otherKey=other&db=0", Route::query("key=value&otherKey=other", ["db" => "0"]));
        $this->assertEquals("?db=0", Route::query("?", ["db" => "0"]));
        $this->assertEquals("?key=value&db=0", Route::query("?key=value", ["db" => "0"]));
        $this->assertEquals("?key=value&db=0", Route::query("?key=value&otherKey", ["db" => "0"]));
        $this->assertEquals("?key=value&otherKey=other&db=0", Route::query("?key=value&otherKey=other", ["db" => "0"]));
    }

    public function testRoutePage(): void
    {
        $this->assertEquals("", Route::page(null, ['db' => null]));
        $this->assertEquals("?db=0", Route::page(null, ['db' => 0]));
        $this->assertEquals("?key=value", Route::page(null, ['key' => 'value', 'db' => null]));
        $this->assertEquals("?key=value&db=0", Route::page(null, ['key' => 'value', 'db' => 0]));
        $this->assertEquals("?key=value&db=0", Route::page(null, ['key' => 'value', 'otherKey' => null, 'db' => 0]));
        $this->assertEquals("?key=value&otherKey=other&db=0", Route::page(null, ['key' => 'value', 'otherKey' => 'other', 'db' => 0]));
        $this->assertEquals("?page=1", Route::page(1, ['db' => null]));
        $this->assertEquals("?page=1&db=0", Route::page(1, ['db' => 0]));
        $this->assertEquals("?page=1&key=value", Route::page(1, ['key' => 'value', 'db' => null]));
        $this->assertEquals("?page=1&key=value&db=0", Route::page(1, ['key' => 'value', 'db' => 0]));
        $this->assertEquals("?page=1&key=value&db=0", Route::page(1, ['key' => 'value', 'otherKey' => null, 'db' => 0]));
        $this->assertEquals("?page=1&key=value&otherKey=other&db=0", Route::page(1, ['key' => 'value', 'otherKey' => 'other', 'db' => 0]));
    }

    /**
     * FALSE is returned if the create_function failed (meaning there was a syntax error)
     * @param mixed $template
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerTemplate')]
    public function testServerSideRender($template)
    {
        $_COOKIE["template"] = $template;
        $this->assertNull(Format::serverSideRender(null, $template));

        $request = new Request();
        $renderer = new JsonRenderer();
        $data = $renderer->getJson($request, true);
        $output = Format::serverSideRender($data, $template);

        $old = libxml_use_internal_errors(true);
        $html = new DOMDocument();
        $html->loadHTML("<html><head></head><body>" . $output . "</body></html>");
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            // ignore invalid tags from HTML5 which libxml doesn't know about
            if ($error->code == 801) {
                continue;
            }
            $this->fail("Error parsing output for server-side rendering of template " . $template . "\n" . $error->message . "\n" . $output);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($old);

        unset($_COOKIE['template']);
    }

    /**
     * The function for the head of the HTML catalog
     * @param mixed $templateName
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerTemplate')]
    public function testGenerateHeader($templateName)
    {
        $_SERVER["HTTP_USER_AGENT"] = "Firefox";
        $_COOKIE["template"] = $templateName;
        $request = new Request();
        $renderer = new HtmlRenderer();
        $tpl = $renderer->getDotTemplate(__DIR__ . '/../templates/' . $templateName . '/file.html');
        $data = $renderer->getTemplateData($request);

        $head = $tpl($data);
        $this->assertStringContainsString("<head>", $head);
        $this->assertStringContainsString("</head>", $head);

        unset($_COOKIE["template"]);
    }

    /**
     * Summary of providerTemplate
     * @return array<mixed>
     */
    public static function providerTemplate()
    {
        return [
            ["bootstrap2"],
            ["bootstrap"],
            ["default"],
        ];
    }

    public function testLocalize(): void
    {
        $this->assertEquals("Authors", localize("authors.title"));

        $this->assertEquals("unknow.key", localize("unknow.key"));
    }

    public function testLocalizeFr(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3";
        $translator = new Translation($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertEquals("Auteurs", $translator->localize("authors.title", -1, true));

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        localize("authors.title", -1, true);
    }

    public function testLocalizeUnknown(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "aa";
        $translator = new Translation($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertEquals("Authors", $translator->localize("authors.title", -1, true));

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        localize("authors.title", -1, true);
    }

    /**
     * @param mixed $acceptLanguage
     * @param mixed $result
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerGetLangAndTranslationFile')]
    public function testGetLangAndTranslationFile($acceptLanguage, $result)
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        $translator = new Translation($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        [$lang, $lang_file] = $translator->getLangAndTranslationFile();
        $this->assertEquals($result, $lang);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        localize("authors.title", -1, true);
    }

    /**
     * Summary of providerGetLangAndTranslationFile
     * @return array<mixed>
     */
    public static function providerGetLangAndTranslationFile()
    {
        return [
            ["en", "en"],
            ["fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3", "fr"],
            ["fr-FR", "fr"],
            ["pt,en-us;q=0.7,en;q=0.3", "en"],
            ["pt-br,pt;q=0.8,en-us;q=0.5,en;q=0.3", "pt_BR"],
            ["pt-pt,pt;q=0.8,en;q=0.5,en-us;q=0.3", "pt_PT"],
            ["zl", "en"],
        ];
    }

    /**
     * @param mixed $acceptLanguage
     * @param mixed $result
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerGetAcceptLanguages')]
    public function testGetAcceptLanguages($acceptLanguage, $result)
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        $translator = new Translation($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $langs = array_keys($translator->getAcceptLanguages($acceptLanguage));
        $this->assertEquals($result, $langs[0]);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        localize("authors.title", -1, true);
    }

    /**
     * Summary of providerGetAcceptLanguages
     * @return array<mixed>
     */
    public static function providerGetAcceptLanguages()
    {
        return [
            ["en", "en"],
            ["en-US", "en_US"],
            ["fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3", "fr"], // French locale with Firefox
            ["fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4", "fr_FR"], // French locale with Chrome
            ["fr-FR", "fr_FR"], // French locale with IE11
            ["pt-br,pt;q=0.8,en-us;q=0.5,en;q=0.3", "pt_BR"],
            ["zl", "zl"],
        ];
    }

    public function testBaseFunction(): void
    {
        $this->assertFalse(Database::isMultipleDatabaseEnabled());
        $this->assertEquals(["" => __DIR__ . "/BaseWithSomeBooks/"], Database::getDbList());

        Config::set('calibre_directory', [
            "Some books" => __DIR__ . "/BaseWithSomeBooks/",
            "One book" => __DIR__ . "/BaseWithOneBook/"]);
        Database::clearDb();

        $this->assertTrue(Database::isMultipleDatabaseEnabled());
        $this->assertEquals("Some books", Database::getDbName(0));
        $this->assertEquals("One book", Database::getDbName(1));
        $this->assertEquals(Config::get('calibre_directory'), Database::getDbList());

        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        Database::clearDb();
    }

    public function testCheckDatabaseAvailability_1(): void
    {
        $this->assertTrue(Database::checkDatabaseAvailability(null));
    }

    public function testCheckDatabaseAvailability_2(): void
    {
        Config::set('calibre_directory', [
            "Some books" => __DIR__ . "/BaseWithSomeBooks/",
            "One book" => __DIR__ . "/BaseWithOneBook/"]);
        Database::clearDb();

        $this->assertTrue(Database::checkDatabaseAvailability(null));

        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        Database::clearDb();
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Database <1> not found.
     */
    public function testCheckDatabaseAvailability_Exception1(): void
    {
        Config::set('calibre_directory', [
            "Some books" => __DIR__ . "/BaseWithSomeBooks/",
            "One book" => __DIR__ . "/OneBook/"]);
        Database::clearDb();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database <1> not found.');

        $this->assertTrue(Database::checkDatabaseAvailability(null));

        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        Database::clearDb();
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Database <0> not found.
     */
    public function testCheckDatabaseAvailability_Exception2(): void
    {
        Config::set('calibre_directory', [
            "Some books" => __DIR__ . "/SomeBooks/",
            "One book" => __DIR__ . "/BaseWithOneBook/"]);
        Database::clearDb();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database <0> not found.');

        $this->assertTrue(Database::checkDatabaseAvailability(null));

        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        Database::clearDb();
    }

    /*
    Test normalized utf8 string according to unicode.org output
    more here :
    http://unicode.org/cldr/utility/transform.jsp?a=Latin-ASCII&b=%C3%80%C3%81%C3%82%C3%83%C3%84%C3%85%C3%87%C3%88%C3%89%C3%8A%C3%8B%C3%8C%C3%8D%C3%8E%C3%8F%C5%92%C3%92%C3%93%C3%94%C3%95%C3%96%C3%99%C3%9A%C3%9B%C3%9C%C3%9D%C3%A0%C3%A1%C3%A2%C3%A3%C3%A4%C3%A5%C3%A7%C3%A8%C3%A9%C3%AA%C3%AB%C3%AC%C3%AD%C3%AE%C3%AF%C5%93%C3%B0%C3%B2%C3%B3%C3%B4%C3%B5%C3%B6%C3%B9%C3%BA%C3%BB%C3%BC%C3%BD%C3%BF%C3%B1
    */
    public function testNormalizeUtf8String(): void
    {
        $this->assertEquals(
            "AAAAAACEEEEIIIIOEOOOOOUUUUYaaaaaaceeeeiiiioedooooouuuuyyn",
            Translation::normalizeUtf8String("ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏŒÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïœðòóôõöùúûüýÿñ")
        );
    }
}
