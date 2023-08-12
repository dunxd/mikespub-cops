<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Calibre;

class CustomColumnTypeBool extends CustomColumnType
{
    public const SQL_BOOKLIST_TRUE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value = 1 {1} order by books.sort';
    public const SQL_BOOKLIST_FALSE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value = 0 {1} order by books.sort';

    // PHP pre 5.6 does not support const arrays
    private $BOOLEAN_NAMES = [
        -1 => "customcolumn.boolean.unknown", // localize("customcolumn.boolean.unknown")
        0 => "customcolumn.boolean.no",      // localize("customcolumn.boolean.no")
        +1 => "customcolumn.boolean.yes",     // localize("customcolumn.boolean.yes")
    ];

    protected function __construct($pcustomId, $database)
    {
        parent::__construct($pcustomId, self::CUSTOM_TYPE_BOOL, $database);
    }

    public function getQuery($id)
    {
        if ($id == -1 || $id === '') {
            $query = str_format(self::SQL_BOOKLIST_NULL, "{0}", "{1}", $this->getTableName());
            return [$query, []];
        } elseif ($id == 0) {
            $query = str_format(self::SQL_BOOKLIST_FALSE, "{0}", "{1}", $this->getTableName());
            return [$query, []];
        } elseif ($id == 1) {
            $query = str_format(self::SQL_BOOKLIST_TRUE, "{0}", "{1}", $this->getTableName());
            return [$query, []];
        } else {
            return null;
        }
    }

    public function getFilter($id, $parentTable = null)
    {
        $linkTable = $this->getTableName();
        $linkColumn = "value";
        // @todo support $parentTable if relevant
        if ($id == -1 || $id === '') {
            // @todo is this the right way when filtering?
            $filter = "not exists (select null from {$linkTable} where {$linkTable}.book = books.id)";
            return [$filter, []];
        } elseif ($id == 0) {
            $filter = "exists (select null from {$linkTable} where {$linkTable}.book = books.id and {$linkTable}.{$linkColumn} = 0)";
            return [$filter, []];
        } elseif ($id == 1) {
            $filter = "exists (select null from {$linkTable} where {$linkTable}.book = books.id and {$linkTable}.{$linkColumn} = 1)";
            return [$filter, []];
        } else {
            return ["", []];
        }
    }

    public function getCustom($id)
    {
        return new CustomColumn($id, localize($this->BOOLEAN_NAMES[$id]), $this);
    }

    protected function getAllCustomValuesFromDatabase($n = -1, $sort = null)
    {
        $queryFormat = "SELECT coalesce({0}.value, -1) AS id, count(*) AS count FROM books LEFT JOIN {0} ON  books.id = {0}.book GROUP BY {0}.value ORDER BY {0}.value";
        $query = str_format($queryFormat, $this->getTableName());
        $result = Database::query($query, [], $this->databaseId);

        $entryArray = [];
        while ($post = $result->fetchObject()) {
            $name = localize($this->BOOLEAN_NAMES[$post->id]);
            $customcolumn = new CustomColumn($post->id, $name, $this);
            array_push($entryArray, $customcolumn->getEntry($post->count));
        }
        return $entryArray;
    }

    public function getDistinctValueCount()
    {
        return count($this->BOOLEAN_NAMES);
    }

    public function getContent($count = 0)
    {
        return localize("customcolumn.description.bool");
    }

    public function getCustomByBook($book)
    {
        $queryFormat = "SELECT {0}.value AS boolvalue FROM {0} WHERE {0}.book = ?";
        $query = str_format($queryFormat, $this->getTableName());

        $result = Database::query($query, [$book->id], $this->databaseId);
        if ($post = $result->fetchObject()) {
            return new CustomColumn($post->boolvalue, localize($this->BOOLEAN_NAMES[$post->boolvalue]), $this);
        } else {
            return new CustomColumn(-1, localize($this->BOOLEAN_NAMES[-1]), $this);
        }
    }

    public function isSearchable()
    {
        return true;
    }
}
