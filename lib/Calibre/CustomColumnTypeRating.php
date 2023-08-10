<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Model\Entry;

class CustomColumnTypeRating extends CustomColumnType
{
    protected function __construct($pcustomId, $database)
    {
        parent::__construct($pcustomId, self::CUSTOM_TYPE_RATING, $database);
    }

    /**
     * Get the name of the linking sqlite table for this column
     * (or NULL if there is no linktable)
     *
     * @return string
     */
    private function getTableLinkName()
    {
        return "books_custom_column_{$this->customId}_link";
    }

    /**
     * Get the name of the linking column in the linktable
     *
     * @return string
     */
    private function getTableLinkColumn()
    {
        return "value";
    }

    public function getQuery($id)
    {
        if (empty($id)) {
            $query = str_format(BookList::SQL_BOOKS_BY_CUSTOM_RATING_NULL, "{0}", "{1}", $this->getTableLinkName(), $this->getTableName(), $this->getTableLinkColumn());
            return [$query, []];
        } else {
            $query = str_format(BookList::SQL_BOOKS_BY_CUSTOM_RATING, "{0}", "{1}", $this->getTableLinkName(), $this->getTableName(), $this->getTableLinkColumn());
            return [$query, [$id]];
        }
    }

    public function getFilter($id)
    {
        // @todo do we want to filter on ratings Id or Value here
        return ["", []];
    }

    public function getCustom($id)
    {
        return new CustomColumn($id, str_format(localize("customcolumn.stars", $id / 2), $id / 2), $this);
    }

    protected function getAllCustomValuesFromDatabase($n = -1, $sort = null)
    {
        $queryFormat = "SELECT coalesce({0}.value, 0) AS value, count(*) AS count FROM books  LEFT JOIN {1} ON  books.id = {1}.book LEFT JOIN {0} ON {0}.id = {1}.value GROUP BY coalesce({0}.value, -1)";
        $query = str_format($queryFormat, $this->getTableName(), $this->getTableLinkName());
        $result = Database::query($query, [], $this->databaseId);

        $countArray = [0 => 0, 2 => 0, 4 => 0, 6 => 0, 8 => 0, 10 => 0];
        while ($row = $result->fetchObject()) {
            $countArray[$row->value] = $row->count;
        }

        $entryArray = [];

        // @todo align with other custom columns
        for ($i = 0; $i <= 5; $i++) {
            $count = $countArray[$i * 2];
            $name = str_format(localize("customcolumn.stars", $i), $i);
            $entryid = $this->getEntryId($i * 2);
            $content = $this->getContent($count);
            $linkarray = $this->getLinkArray($i * 2);
            $entry = new Entry($name, $entryid, $content, $this->datatype, $linkarray, $this->getDatabaseId(), "", $count);
            array_push($entryArray, $entry);
        }

        return $entryArray;
    }

    public function getDistinctValueCount()
    {
        return count($this->getAllCustomValues());
    }

    public function getDescription()
    {
        return localize("customcolumn.description.rating");
    }

    public function getCustomByBook($book)
    {
        $queryFormat = "SELECT {0}.value AS value FROM {0}, {1} WHERE {0}.id = {1}.{2} AND {1}.book = ?";
        $query = str_format($queryFormat, $this->getTableName(), $this->getTableLinkName(), $this->getTableLinkColumn());

        $result = Database::query($query, [$book->id], $this->databaseId);
        if ($post = $result->fetchObject()) {
            return new CustomColumn($post->value, str_format(localize("customcolumn.stars", $post->value / 2), $post->value / 2), $this);
        }
        return new CustomColumn(null, localize("customcolumn.rating.unknown"), $this);
    }

    public function isSearchable()
    {
        return true;
    }
}
