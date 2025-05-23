<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (https://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Handlers\BaseHandler;
use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Model\Entry;
use SebLucas\Cops\Pages\PageId;
use SebLucas\Cops\Routing\UriGenerator;

class VirtualLibrary extends Base
{
    public const PAGE_ID = PageId::ALL_LIBRARIES_ID;
    public const PAGE_ALL = PageId::ALL_LIBRARIES;
    public const PAGE_DETAIL = PageId::LIBRARY_DETAIL;
    public const ROUTE_ALL = "page-libraries";
    public const ROUTE_DETAIL = "page-library";
    public const SQL_TABLE = "libraries";
    public const URL_PARAM = "vl";
    public const ALL_WILDCARD = ["*"];

    /** @var array<mixed> */
    protected static array $libraries = [];
    public string $value;

    /**
     * Summary of __construct
     * @param \stdClass $post
     * @param ?int $database
     */
    public function __construct($post, $database = null)
    {
        $this->id = $post->id;
        $this->name = $post->name;
        $this->value = $post->value;
        $this->databaseId = $database;
    }

    /**
     * Summary of getUri
     * @param array<mixed> $params
     * @return string
     */
    public function getUri($params = [])
    {
        // get home page from Config
        $params['page'] = PageId::getHomePage();
        // we need databaseId here because we use Route::link with $handler
        $params['db'] = $this->getDatabaseId();
        if (isset($this->id)) {
            // URL format: ...&vl=2.Short_Stories_in_English
            $params[self::URL_PARAM] = self::formatParameter($this->id, $this->getTitle());
        }
        return $this->getLink($params);
    }

    /**
     * Summary of getParentTitle
     * @return string
     */
    public function getParentTitle()
    {
        return localize("libraries.title");
    }

    /**
     * Summary of formatParameter
     * @param string|int $id
     * @param string $title
     * @return string
     */
    public static function formatParameter($id, $title)
    {
        // URL format: ...&vl=2.Short_Stories_in_English
        return strval($id) . '.' . UriGenerator::slugify($title);
    }

    /**
     * Summary of getLibraries
     * @param ?int $database
     * @return array<string, mixed>
     */
    public static function getLibraries($database = null)
    {
        $db = $database ?? 0;
        if (array_key_exists($db, self::$libraries)) {
            return self::$libraries[$db];
        }
        $preference = Preference::getVirtualLibraries($database);
        self::$libraries[$db] = $preference->val ?? [];
        return self::$libraries[$db];
    }

    /**
     * Summary of countEntries
     * @param ?int $database
     * @return int
     */
    public static function countEntries($database = null)
    {
        $libraryList = Config::get('calibre_virtual_libraries', []);
        if (!empty($libraryList) && $libraryList !== static::ALL_WILDCARD) {
            return count($libraryList);
        }
        $libraries = self::getLibraries($database);
        return count($libraries);
    }

    /**
     * Summary of getEntries
     * @param ?int $database
     * @param class-string<BaseHandler> $handler
     * @return array<Entry>
     */
    public static function getEntries($database, $handler)
    {
        $libraryList = Config::get('calibre_virtual_libraries', []);
        if (!empty($libraryList) && $libraryList === static::ALL_WILDCARD) {
            $libraryList = [];
        }
        $libraries = self::getLibraries($database);
        $entryArray = [];
        $id = 1;
        foreach ($libraries as $name => $value) {
            if (!empty($libraryList) && !in_array($name, $libraryList)) {
                $id += 1;
                continue;
            }
            // @todo get book count filtered by value
            $post = (object) ['id' => $id, 'name' => $name, 'value' => $value, 'count' => 0];
            $instance = new self($post, $database);
            $instance->setHandler($handler);
            array_push($entryArray, $instance->getEntry($post->count));
            $id += 1;
        }
        return $entryArray;
    }

    /**
     * Summary of getWithoutEntry
     * @param ?int $database
     * @param class-string<BaseHandler> $handler
     * @return ?Entry
     */
    public static function getWithoutEntry($database, $handler)
    {
        $booklist = new BookList(null, $database);
        $count = $booklist->getBookCount();
        $instance = self::getInstanceById(null, $database);
        $instance->setHandler($handler);
        return $instance->getEntry($count);
    }

    /**
     * Summary of getDefaultName
     * @return ?string
     */
    public static function getDefaultName()
    {
        return localize("libraries.none");
    }

    /**
     * Summary of getCount
     * @param ?int $database
     * @param class-string<BaseHandler> $handler
     * @return ?Entry
     */
    public static function getCount($database, $handler)
    {
        $libraries = self::getLibraries($database);
        $count = count($libraries);
        return self::getCountEntry($count, $database, "libraries", $handler);
    }

    /**
     * Summary of getInstanceById
     * @param string|int|null $id
     * @param ?int $database
     * @return self
     */
    public static function getInstanceById($id, $database = null)
    {
        $libraries = self::getLibraries($database);
        if (!empty($id)) {
            // id = key position in array + 1
            $id = intval($id) - 1;
            $name = array_keys($libraries)[$id] ?? null;
            if (!empty($name)) {
                return self::getInstanceByName($name);
            }
        }
        $default = self::getDefaultName();
        // use id = 0 to support route urls
        $post = (object) ['id' => 0, 'name' => $default, 'value' => ''];
        return new self($post, $database);
    }

    /**
     * Summary of getInstanceByName
     * @param string $name
     * @param ?int $database
     * @return self|null
     */
    public static function getInstanceByName($name, $database = null)
    {
        $libraries = self::getLibraries($database);
        if (!empty($libraries) && array_key_exists($name, $libraries)) {
            // id = key position in array + 1
            $id = array_search($name, array_keys($libraries)) + 1;
            $post = (object) ['id' => $id, 'name' => $name, 'value' => $libraries[$name]];
            return new self($post, $database);
        }
        return null;
    }
}
