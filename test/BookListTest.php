<?php
/**
 * COPS (Calibre OPDS PHP Server) test file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Tests;

use SebLucas\Cops\Calibre\BookList;

require_once __DIR__ . '/config_test.php';
use PHPUnit\Framework\TestCase;
use SebLucas\Cops\Calibre\Database;
use SebLucas\Cops\Calibre\Author;
use SebLucas\Cops\Calibre\Language;
use SebLucas\Cops\Calibre\Publisher;
use SebLucas\Cops\Calibre\Rating;
use SebLucas\Cops\Calibre\Serie;
use SebLucas\Cops\Calibre\Tag;
use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Input\Request;

class BookListTest extends TestCase
{
    private static Request $request;

    public static function setUpBeforeClass(): void
    {
        Config::set('calibre_directory', __DIR__ . "/BaseWithSomeBooks/");
        self::$request = new Request();
        Database::clearDb();
    }

    public function testGetBookCount(): void
    {
        $booklist = new BookList(self::$request);
        $this->assertEquals(16, $booklist->getBookCount());
    }

    public function testGetCount(): void
    {
        $booklist = new BookList(self::$request);

        $entryArray = $booklist->getCount();
        $this->assertEquals(2, count($entryArray));

        $entryAllBooks = $entryArray [0];
        $this->assertEquals("Alphabetical index of the 16 books", $entryAllBooks->content);

        $entryRecentBooks = $entryArray [1];
        $this->assertEquals("16 most recent books", $entryRecentBooks->content);
    }

    public function testGetCountRecent(): void
    {
        Config::set('recentbooks_limit', 0);
        $request = new Request();
        $booklist = new BookList($request);

        $entryArray = $booklist->getCount();
        $this->assertEquals(1, count($entryArray));

        Config::set('recentbooks_limit', 2);
        $request = new Request();
        $booklist = new BookList($request);

        $entryArray = $booklist->getCount();
        $entryRecentBooks = $entryArray [1];
        $this->assertEquals("2 most recent books", $entryRecentBooks->content);

        Config::set('recentbooks_limit', 50);
    }

    public function testGetBooksByAuthor(): void
    {
        // All book by Arthur Conan Doyle
        Config::set('max_item_per_page', 5);
        $request = new Request();
        $booklist = new BookList($request);
        /** @var Author $author */
        $author = Author::getInstanceById(1);

        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($author, 1);
        $this->assertEquals(5, count($entryArray));
        $this->assertEquals(8, $totalNumber);

        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($author, 2);
        $this->assertEquals(3, count($entryArray));
        $this->assertEquals(8, $totalNumber);

        Config::set('max_item_per_page', -1);
        $request = new Request();
        $booklist = new BookList($request);

        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($author, -1);
        $this->assertEquals(8, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetBooksBySeries(): void
    {
        $booklist = new BookList(self::$request);
        /** @var Serie $series */
        $series = Serie::getInstanceById(1);

        // All book from the Sherlock Holmes series
        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($series, -1);
        $this->assertEquals(7, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetBooksByPublisher(): void
    {
        $booklist = new BookList(self::$request);
        /** @var Publisher $publisher */
        $publisher = Publisher::getInstanceById(6);

        // All books from Strand Magazine
        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($publisher, -1);
        $this->assertEquals(8, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetBooksByTag(): void
    {
        $booklist = new BookList(self::$request);
        /** @var Tag $tag */
        $tag = Tag::getInstanceById(1);

        // All book with the Fiction tag
        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($tag, -1);
        $this->assertEquals(14, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetBooksByLanguage(): void
    {
        $booklist = new BookList(self::$request);
        /** @var Language $language */
        $language = Language::getInstanceById(1);

        // All english book (= all books)
        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($language, -1);
        $this->assertEquals(14, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetBooksByRating(): void
    {
        $booklist = new BookList(self::$request);
        /** @var Rating $rating */
        $rating = Rating::getInstanceById(1);

        // All books with 4 stars
        [$entryArray, $totalNumber] = $booklist->getBooksByInstance($rating, -1);
        $this->assertEquals(4, count($entryArray));
        $this->assertEquals(-1, $totalNumber);
    }

    public function testGetCountByFirstLetter(): void
    {
        $booklist = new BookList(self::$request);

        // All books by first letter
        $entryArray = $booklist->getCountByFirstLetter();
        $this->assertCount(10, $entryArray);
    }

    public function testGetBooksByFirstLetter(): void
    {
        $booklist = new BookList(self::$request);

        // All books by first letter
        [$entryArray, $totalNumber] = $booklist->getBooksByFirstLetter("T", -1);
        $this->assertEquals(-1, $totalNumber);
        $this->assertCount(3, $entryArray);
    }

    public function testGetCountByPubYear(): void
    {
        $booklist = new BookList(self::$request);

        // All books by publication year
        $entryArray = $booklist->getCountByPubYear();
        $this->assertCount(6, $entryArray);
    }

    public function testGetBooksByPubYear(): void
    {
        $booklist = new BookList(self::$request);

        // All books by publication year
        [$entryArray, $totalNumber] = $booklist->getBooksByPubYear(2006, -1);
        $this->assertEquals(-1, $totalNumber);
        $this->assertCount(9, $entryArray);
    }

    public function testGetBatchQuery(): void
    {
        // All recent books
        $request = new Request();
        // Use anonymous class to override class constant
        $booklist = new class ($request) extends BookList {
            public const BATCH_QUERY = true;
        };

        $entryArray = $booklist->getAllRecentBooks();
        $this->assertCount(16, $entryArray);
    }

    public function testGetAllRecentBooks(): void
    {
        // All recent books
        Config::set('recentbooks_limit', 2);
        $request = new Request();
        $booklist = new BookList($request);

        $entryArray = $booklist->getAllRecentBooks();
        $this->assertCount(2, $entryArray);

        Config::set('recentbooks_limit', 50);
        $request = new Request();
        $booklist = new BookList($request);

        $entryArray = $booklist->getAllRecentBooks();
        $this->assertCount(16, $entryArray);
    }

    public function tearDown(): void
    {
        Database::clearDb();
    }
}