<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     At Libitum <eljarec@yahoo.com>
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Pages\Page;

class Publisher extends Base
{
    public const PAGE_ID = Page::ALL_PUBLISHERS_ID;
    public const PAGE_ALL = Page::ALL_PUBLISHERS;
    public const PAGE_DETAIL = Page::PUBLISHER_DETAIL;
    public const SQL_TABLE = "publishers";
    public const SQL_LINK_TABLE = "books_publishers_link";
    public const SQL_LINK_COLUMN = "publisher";
    public const SQL_SORT = "name";
    public const SQL_COLUMNS = "publishers.id as id, publishers.name as name, count(*) as count";
    public const SQL_ALL_ROWS = "select {0} from publishers, books_publishers_link where publishers.id = publisher {1} group by publishers.id, publishers.name order by publishers.name";
    public const SQL_ROWS_FOR_SEARCH = "select {0} from publishers, books_publishers_link where publishers.id = publisher and upper (publishers.name) like ? {1} group by publishers.id, publishers.name order by publishers.name";
    public const SQL_BOOKLIST = 'select {0} from books_publishers_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_publishers_link.book = books.id and publisher = ? {1} order by books.sort';
    public const URL_PARAM = "p";

    public $id;
    public $name;

    public function __construct($post, $database = null)
    {
        $this->id = $post->id;
        $this->name = $post->name;
        $this->databaseId = $database;
    }

    public function getUri()
    {
        return "?page=".self::PAGE_DETAIL."&id=$this->id";
    }

    public function getEntryId()
    {
        return self::PAGE_ID.":".$this->id;
    }

    public function getParentTitle()
    {
        return localize("publishers.title");
    }

    /** Use inherited class methods to query static SQL_TABLE for this class */

    public static function getPublisherByBookId($bookId, $database = null)
    {
        $query = 'select publishers.id as id, name
from books_publishers_link, publishers
where publishers.id = publisher and book = ?';
        $result = Database::query($query, [$bookId], $database);
        if ($post = $result->fetchObject()) {
            return new Publisher($post, $database);
        }
        return null;
    }

    /**
     * Summary of getPublisherById
     * @param mixed $publisherId
     * @param mixed $database
     * @return Publisher
     */
    public static function getPublisherById($publisherId, $database = null)
    {
        return self::getInstanceById($publisherId, localize("publisherword.none"), $database);
    }
}
