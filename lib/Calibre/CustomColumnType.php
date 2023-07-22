<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Model\Entry;
use SebLucas\Cops\Model\LinkNavigation;
use SebLucas\Cops\Pages\Page;
use Exception;

/**
 * A single calibre custom column
 */
abstract class CustomColumnType extends Base
{
    public const PAGE_ID = Page::ALL_CUSTOMS_ID;
    public const PAGE_ALL = Page::ALL_CUSTOMS;
    public const PAGE_DETAIL = Page::CUSTOM_DETAIL;
    public const SQL_TABLE = "custom_columns";
    public const ALL_WILDCARD         = ["*"];

    public const CUSTOM_TYPE_TEXT      = "text";        // type 1 + 2 (calibre)
    public const CUSTOM_TYPE_CSV       = "csv";         // type 2 (internal)
    public const CUSTOM_TYPE_COMMENT   = "comments";    // type 3
    public const CUSTOM_TYPE_SERIES    = "series";      // type 4
    public const CUSTOM_TYPE_ENUM      = "enumeration"; // type 5
    public const CUSTOM_TYPE_DATE      = "datetime";    // type 6
    public const CUSTOM_TYPE_FLOAT     = "float";       // type 7
    public const CUSTOM_TYPE_INT       = "int";         // type 8
    public const CUSTOM_TYPE_RATING    = "rating";      // type 9
    public const CUSTOM_TYPE_BOOL      = "bool";        // type 10
    public const CUSTOM_TYPE_COMPOSITE = "composite";   // type 11 + 12

    /** @var array<int, CustomColumnType>  */
    private static $customColumnCacheID = [];

    /** @var array<string, CustomColumnType>  */
    private static $customColumnCacheLookup = [];

    /** @var integer the id of this column */
    public $customId;
    /** @var string name/title of this column */
    public $columnTitle;
    /** @var string the datatype of this column (one of the CUSTOM_TYPE_* constant values) */
    public $datatype;
    /** @var null|Entry[] */
    private $customValues = null;

    protected function __construct($pcustomId, $pdatatype, $database = null)
    {
        $this->columnTitle = self::getTitleByCustomID($pcustomId, $database);
        $this->customId = $pcustomId;
        $this->datatype = $pdatatype;
        $this->customValues = null;
        $this->databaseId = $database;
    }

    /**
     * The URI to show all book swith a specific value in this column
     *
     * @param string|integer $id the id of the value to show
     * @return string
     */
    public function getUri($id = null)
    {
        return "?page=" . self::PAGE_DETAIL . "&custom={$this->customId}&id={$id}";
    }

    /**
     * The URI to show all the values of this column
     *
     * @return string
     */
    public function getUriAllCustoms()
    {
        return "?page=" . self::PAGE_ALL . "&custom={$this->customId}";
    }

    /**
     * The EntryID to show all book swith a specific value in this column
     *
     * @param string|integer $id the id of the value to show
     * @return string
     */
    public function getEntryId($id = null)
    {
        return self::PAGE_ID . ":" . $this->customId . ":" . $id;
    }

    /**
     * The EntryID to show all the values of this column
     *
     * @return string
     */
    public function getAllCustomsId()
    {
        return self::PAGE_ID . ":" . $this->customId;
    }

    /**
     * The title of this column
     *
     * @return string
     */
    public function getName()
    {
        return $this->columnTitle;
    }

    public function getTitle()
    {
        return $this->columnTitle;
    }

    public function getContentType()
    {
        return $this->datatype;
    }

    public function getLinkArray($id = null)
    {
        return [ new LinkNavigation($this->getUri($id), null, null, $this->getDatabaseId()) ];
    }

    /**
     * The description used in the index page
     *
     * @return string
     */
    public function getDescription()
    {
        $desc = $this->getDatabaseDescription();
        if ($desc === null || empty($desc)) {
            $desc = str_format(localize("customcolumn.description"), $this->getTitle());
        }
        return $desc;
    }

    /**
     * The description of this column as it is definied in the database
     *
     * @return string|null
     */
    public function getDatabaseDescription()
    {
        $result = $this->getDb($this->databaseId)->prepare('SELECT display FROM custom_columns WHERE id = ?');
        $result->execute([$this->customId]);
        if ($post = $result->fetchObject()) {
            $json = json_decode($post->display);
            return (isset($json->description) && !empty($json->description)) ? $json->description : null;
        }
        return null;
    }

    /**
     * Get the Entry for this column
     * This is used in the initializeContent method to display e.g. the index page
     *
     * @return Entry
     */
    public function getCount()
    {
        $ptitle = $this->getTitle();
        $pid = $this->getAllCustomsId();
        $pcontent = $this->getDescription();
        // @checkme convert "csv" back to "text" here?
        $pcontentType = $this->datatype;
        $database = $this->getDatabaseId();
        $plinkArray = [new LinkNavigation($this->getUriAllCustoms(), null, null, $database)];
        $pclass = "";
        $pcount = $this->getDistinctValueCount();

        return new Entry($ptitle, $pid, $pcontent, $pcontentType, $plinkArray, $database, $pclass, $pcount);
    }

    /**
     * Get the amount of distinct values for this column
     *
     * @return int
     */
    protected function getDistinctValueCount()
    {
        return count($this->getAllCustomValues());
    }

    /**
     * Encode a value of this column ready to be displayed in an HTML document
     *
     * @param integer|string $value
     * @return string
     */
    public function encodeHTMLValue($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * Get the datatype of a CustomColumn by its customID
     *
     * @param integer $customId
     * @return string|null
     */
    private static function getDatatypeByCustomID($customId, $database = null)
    {
        $result = parent::getDb($database)->prepare('SELECT datatype, is_multiple FROM custom_columns WHERE id = ?');
        $result->execute([$customId]);
        if ($post = $result->fetchObject()) {
            // handle case where we have several values, e.g. array of text for type 2 (csv)
            if ($post->datatype === "text" && $post->is_multiple === 1) {
                return "csv";
            }
            return $post->datatype;
        }
        return null;
    }

    /**
     * Create a CustomColumnType by CustomID
     *
     * @param integer $customId the id of the custom column
     * @return CustomColumnType|null
     * @throws Exception If the $customId is not found or the datatype is unknown
     */
    public static function createByCustomID($customId, $database = null)
    {
        // Reuse already created CustomColumns for performance
        if (array_key_exists($customId, self::$customColumnCacheID)) {
            return self::$customColumnCacheID[$customId];
        }

        $datatype = self::getDatatypeByCustomID($customId, $database);

        switch ($datatype) {
            case self::CUSTOM_TYPE_TEXT:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeText($customId, self::CUSTOM_TYPE_TEXT, $database);
            case self::CUSTOM_TYPE_CSV:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeText($customId, self::CUSTOM_TYPE_CSV, $database);
            case self::CUSTOM_TYPE_SERIES:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeSeries($customId, $database);
            case self::CUSTOM_TYPE_ENUM:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeEnumeration($customId, $database);
            case self::CUSTOM_TYPE_COMMENT:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeComment($customId, $database);
            case self::CUSTOM_TYPE_DATE:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeDate($customId, $database);
            case self::CUSTOM_TYPE_FLOAT:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeFloat($customId, $database);
            case self::CUSTOM_TYPE_INT:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeInteger($customId, self::CUSTOM_TYPE_INT, $database);
            case self::CUSTOM_TYPE_RATING:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeRating($customId, $database);
            case self::CUSTOM_TYPE_BOOL:
                return self::$customColumnCacheID[$customId] = new CustomColumnTypeBool($customId, $database);
            case self::CUSTOM_TYPE_COMPOSITE:
                return null; //TODO Currently not supported
            default:
                throw new Exception("Unkown column type: " . $datatype);
        }
    }

    /**
     * Create a CustomColumnType by its lookup name
     *
     * @param string $lookup the lookup-name of the custom column
     * @return CustomColumnType|null
     */
    public static function createByLookup($lookup, $database = null)
    {
        // Reuse already created CustomColumns for performance
        if (array_key_exists($lookup, self::$customColumnCacheLookup)) {
            return self::$customColumnCacheLookup[$lookup];
        }

        $result = parent::getDb($database)->prepare('SELECT id FROM custom_columns WHERE label = ?');
        $result->execute([$lookup]);
        if ($post = $result->fetchObject()) {
            return self::$customColumnCacheLookup[$lookup] = self::createByCustomID($post->id, $database);
        }
        return self::$customColumnCacheLookup[$lookup] = null;
    }

    /**
     * Return an entry array for all possible (in the DB used) values of this column
     * These are the values used in the getUriAllCustoms() page
     *
     * @return Entry[]
     */
    public function getAllCustomValues()
    {
        // lazy loading
        if ($this->customValues == null) {
            $this->customValues = $this->getAllCustomValuesFromDatabase();
        }

        return $this->customValues;
    }

    /**
     * Get the title of a CustomColumn by its customID
     *
     * @param integer $customId
     * @return string
     */
    protected static function getTitleByCustomID($customId, $database = null)
    {
        $result = parent::getDb($database)->prepare('SELECT name FROM custom_columns WHERE id = ?');
        $result->execute([$customId]);
        if ($post = $result->fetchObject()) {
            return $post->name;
        }
        return "";
    }

    /**
     * Check the list of custom columns requested (and expand the wildcard if needed)
     *
     * @param array<string> $columnList
     * @return array<string>
     */
    public static function checkCustomColumnList($columnList, $database = null)
    {
        if ($columnList === self::ALL_WILDCARD) {
            $columnList = array_keys(self::getAllCustomColumns($database));
        }
        return $columnList;
    }

    /**
     * Get all defined custom columns from the database
     *
     * @return array<string, array>
     */
    public static function getAllCustomColumns($database = null)
    {
        $result = parent::getDb($database)->prepare('SELECT id, label, name, datatype, display, is_multiple, normalized FROM custom_columns');
        $result->execute();
        $columns = [];
        while ($post = $result->fetchObject()) {
            $columns[$post->label] = (array) $post;
        }
        return $columns;
    }

    /**
     * Get the query to find all books with a specific value of this column
     * the returning array has two values:
     *  - first the query (string)
     *  - second an array of all PreparedStatement parameters
     *
     * @param string|integer|null $id the id of the searched value
     * @return array|null
     */
    abstract public function getQuery($id);

    /**
     * Get a CustomColumn for a specified (by ID) value
     *
     * @param string|integer $id the id of the searched value
     * @return CustomColumn|null
     */
    abstract public function getCustom($id);

    /**
     * Return an entry array for all possible (in the DB used) values of this column by querying the database
     *
     * @return Entry[]|null
     */
    abstract protected function getAllCustomValuesFromDatabase();

    /**
     * Find the value of this column for a specific book
     *
     * @param Book $book
     * @return CustomColumn
     */
    abstract public function getCustomByBook($book);

    /**
     * Is this column searchable by value
     * only searchable columns can be displayed on the index page
     *
     * @return bool
     */
    abstract public function isSearchable();
}
