<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Model\Entry;
use SebLucas\Cops\Model\EntryBook;
use SebLucas\Cops\Model\LinkNavigation;
use SebLucas\Cops\Pages\Page;
use SebLucas\Cops\Pages\PageQueryResult;

class BookList extends Base
{
    public const SQL_BOOKS_ALL = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . ' where 1=1 {1} order by books.sort ';
    public const SQL_BOOKS_BY_PUBLISHER = 'select {0} from books_publishers_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_publishers_link.book = books.id and publisher = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_FIRST_LETTER = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where upper (books.sort) like ? {1} order by books.sort';
    public const SQL_BOOKS_BY_PUB_YEAR = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where substr(date(books.pubdate), 1, 4) = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_AUTHOR = 'select {0} from books_authors_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    left outer join books_series_link on books_series_link.book = books.id
    where books_authors_link.book = books.id and author = ? {1} order by series desc, series_index asc, pubdate asc';
    public const SQL_BOOKS_BY_SERIE = 'select {0} from books_series_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_series_link.book = books.id and series = ? {1} order by series_index';
    public const SQL_BOOKS_BY_TAG = 'select {0} from books_tags_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_tags_link.book = books.id and tag = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_LANGUAGE = 'select {0} from books_languages_link, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_languages_link.book = books.id and lang_code = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.{3} = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_BOOL_TRUE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value = 1 {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_BOOL_FALSE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value = 0 {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_NULL = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books.id not in (select book from {2}) {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_RATING = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    left join {2} on {2}.book = books.id
    left join {3} on {3}.id = {2}.{4}
    where {3}.value = ?  order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_RATING_NULL = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    left join {2} on {2}.book = books.id
    left join {3} on {3}.id = {2}.{4}
    where ((books.id not in (select {2}.book from {2})) or ({3}.value = 0)) {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_DATE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and date({2}.value) = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_YEAR = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and substr(date({2}.value), 1, 4) = ? {1} order by {2}.value';
    public const SQL_BOOKS_BY_CUSTOM_DIRECT = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_CUSTOM_RANGE = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.value >= ? and {2}.value <= ? {1} order by {2}.value';
    public const SQL_BOOKS_BY_CUSTOM_DIRECT_ID = 'select {0} from {2}, books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where {2}.book = books.id and {2}.id = ? {1} order by books.sort';
    public const SQL_BOOKS_QUERY = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where (
    exists (select null from authors, books_authors_link where book = books.id and author = authors.id and authors.name like ?) or
    exists (select null from tags, books_tags_link where book = books.id and tag = tags.id and tags.name like ?) or
    exists (select null from series, books_series_link on book = books.id and books_series_link.series = series.id and series.name like ?) or
    exists (select null from publishers, books_publishers_link where book = books.id and books_publishers_link.publisher = publishers.id and publishers.name like ?) or
    title like ?) {1} order by books.sort';
    public const SQL_BOOKS_RECENT = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where 1=1 {1} order by books.timestamp desc limit ';
    public const SQL_BOOKS_BY_RATING = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where books_ratings_link.book = books.id and ratings.id = ? {1} order by books.sort';
    public const SQL_BOOKS_BY_RATING_NULL = 'select {0} from books ' . Book::SQL_BOOKS_LEFT_JOIN . '
    where ((books.id not in (select book from books_ratings_link)) or (ratings.rating = 0)) {1} order by books.sort';

    public const BAD_SEARCH = 'QQQQQ';

    public Request $request;
    protected mixed $numberPerPage = null;
    protected array $ignoredCategories = [];
    public mixed $orderBy = null;

    public function __construct(Request $request, mixed $database = null, mixed $numberPerPage = null)
    {
        $this->request = $request;
        $this->databaseId = $database ?? $this->request->get('db', null, '/^\d+$/');
        $this->numberPerPage = $numberPerPage ?? $this->request->option("max_item_per_page");
        $this->ignoredCategories = $this->request->option('ignored_categories');
        $this->setOrderBy();
    }

    protected function setOrderBy()
    {
        $this->orderBy = $this->request->getSorted();
        //$this->orderBy ??= $this->request->option('sort');
    }

    protected function getOrderBy()
    {
        return match ($this->orderBy) {
            'title' => 'books.sort',
            'author' => 'books.author_sort',
            'pubdate' => 'books.pubdate desc',
            'rating' => 'ratings.rating desc',
            'timestamp' => 'books.timestamp desc',
            default => $this->orderBy,
        };
    }

    public function getBookCount()
    {
        return parent::executeQuerySingle('select count(*) from books', $this->databaseId);
    }

    public function getCount()
    {
        global $config;
        $nBooks = $this->getBookCount();
        $result = [];
        $entry = new Entry(
            localize('allbooks.title'),
            Book::PAGE_ID,
            str_format(localize('allbooks.alphabetical', $nBooks), $nBooks),
            'text',
            [new LinkNavigation('?page='.Book::PAGE_ALL, null, null, $this->databaseId)],
            $this->databaseId,
            '',
            $nBooks
        );
        array_push($result, $entry);
        if ($config['cops_recentbooks_limit'] > 0) {
            $entry = new Entry(
                localize('recent.title'),
                Page::ALL_RECENT_BOOKS_ID,
                str_format(localize('recent.list'), $config['cops_recentbooks_limit']),
                'text',
                [ new LinkNavigation('?page='.Page::ALL_RECENT_BOOKS, null, null, $this->databaseId)],
                $this->databaseId,
                '',
                $config['cops_recentbooks_limit']
            );
            array_push($result, $entry);
        }
        return $result;
    }

    public function getBooksByAuthor($authorId, $n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_AUTHOR, [$authorId], $n);
    }

    public function getBooksByRating($ratingId, $n)
    {
        if (empty($ratingId)) {
            return $this->getBooksWithoutRating($n);
        }
        return $this->getEntryArray(self::SQL_BOOKS_BY_RATING, [$ratingId], $n);
    }

    public function getBooksWithoutRating($n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_RATING_NULL, [], $n);
    }

    public function getBooksByPublisher($publisherId, $n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_PUBLISHER, [$publisherId], $n);
    }

    public function getBooksBySeries($serieId, $n)
    {
        global $config;
        if (empty($serieId) && in_array("series", $config['cops_show_not_set_filter'])) {
            return $this->getBooksWithoutSeries($n);
        }
        return $this->getEntryArray(self::SQL_BOOKS_BY_SERIE, [$serieId], $n);
    }

    public function getBooksWithoutSeries($n)
    {
        $query = str_format(self::SQL_BOOKS_BY_CUSTOM_NULL, "{0}", "{1}", "books_series_link");
        return $this->getEntryArray($query, [], $n);
    }

    public function getBooksByTag($tagId, $n)
    {
        global $config;
        if (empty($tagId) && in_array("tag", $config['cops_show_not_set_filter'])) {
            return $this->getBooksWithoutTag($n);
        }
        return $this->getEntryArray(self::SQL_BOOKS_BY_TAG, [$tagId], $n);
    }

    public function getBooksWithoutTag($n)
    {
        $query = str_format(self::SQL_BOOKS_BY_CUSTOM_NULL, "{0}", "{1}", "books_tags_link");
        return $this->getEntryArray($query, [], $n);
    }

    public function getBooksByLanguage($languageId, $n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_LANGUAGE, [$languageId], $n);
    }

    /**
     * Summary of getBooksByCustom
     * @param CustomColumnType $columnType
     * @param integer $id
     * @param integer $n
     * @return array
     */
    public function getBooksByCustom($columnType, $id, $n)
    {
        [$query, $params] = $columnType->getQuery($id);

        return $this->getEntryArray($query, $params, $n);
    }

    /**
     * Summary of getBooksByCustomYear
     * @param CustomColumnTypeDate $columnType
     * @param mixed $year
     * @param mixed $n
     * @return array
     */
    public function getBooksByCustomYear($columnType, $year, $n)
    {
        [$query, $params] = $columnType->getQueryByYear($year);

        return $this->getEntryArray($query, $params, $n);
    }

    /**
     * Summary of getBooksByCustomRange
     * @param CustomColumnTypeInteger $columnType
     * @param mixed $range
     * @param mixed $n
     * @return array
     */
    public function getBooksByCustomRange($columnType, $range, $n)
    {
        [$query, $params] = $columnType->getQueryByRange($range);

        return $this->getEntryArray($query, $params, $n);
    }

    public function getBooksWithoutCustom($columnType, $n)
    {
        // use null here to reduce conflict with bool and int custom columns
        [$query, $params] = $columnType->getQuery(null);
        return $this->getEntryArray($query, $params, $n);
    }

    public function getBooksByQueryScope($queryScope, $n, $ignoredCategories = [])
    {
        $i = 0;
        $critArray = [];
        foreach ([PageQueryResult::SCOPE_AUTHOR,
                       PageQueryResult::SCOPE_TAG,
                       PageQueryResult::SCOPE_SERIES,
                       PageQueryResult::SCOPE_PUBLISHER,
                       PageQueryResult::SCOPE_BOOK] as $key) {
            if (in_array($key, $ignoredCategories) ||
                (!array_key_exists($key, $queryScope) && !array_key_exists('all', $queryScope))) {
                $critArray[$i] = self::BAD_SEARCH;
            } else {
                if (array_key_exists($key, $queryScope)) {
                    $critArray[$i] = $queryScope[$key];
                } else {
                    $critArray[$i] = $queryScope["all"];
                }
            }
            $i++;
        }
        return $this->getEntryArray(self::SQL_BOOKS_QUERY, $critArray, $n);
    }

    public function getAllBooks($n)
    {
        [$entryArray, $totalNumber] = $this->getEntryArray(self::SQL_BOOKS_ALL, [], $n);
        return [$entryArray, $totalNumber];
    }

    public function getCountByFirstLetter()
    {
        return $this->getCountByGroup('substr(upper(books.sort), 1, 1)', Book::PAGE_LETTER, 'letter');
    }

    public function getCountByPubYear()
    {
        return $this->getCountByGroup('substr(date(books.pubdate), 1, 4)', Book::PAGE_YEAR, 'year');
    }

    public function getCountByGroup($groupField, $page, $label)
    {
        $filter = new Filter($this->request, [], "books", $this->databaseId);
        $filterString = $filter->getFilterString();
        $params = $filter->getQueryParams();

        // @todo check orderBy to sort by count
        /** @var \PDOStatement $result */
        [, $result] = parent::executeQuery('select {0}
from books
where 1=1 {1}
group by groupid
order by groupid', $groupField . ' as groupid, count(*) as count', $filterString, $params, -1, $this->databaseId);

        $entryArray = [];
        while ($post = $result->fetchObject()) {
            array_push($entryArray, new Entry(
                $post->groupid,
                Book::PAGE_ID.':'.$label.':'.$post->groupid,
                str_format(localize('bookword', $post->count), $post->count),
                'text',
                [new LinkNavigation('?page='.$page.'&id='. rawurlencode($post->groupid), null, null, $this->databaseId)],
                $this->databaseId,
                ucfirst($label),
                $post->count
            ));
        }
        return $entryArray;
    }

    public function getBooksByFirstLetter($letter, $n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_FIRST_LETTER, [$letter . '%'], $n);
    }

    public function getBooksByPubYear($year, $n)
    {
        return $this->getEntryArray(self::SQL_BOOKS_BY_PUB_YEAR, [$year], $n);
    }

    public function getAllRecentBooks()
    {
        global $config;
        [$entryArray, ] = $this->getEntryArray(self::SQL_BOOKS_RECENT . $config['cops_recentbooks_limit'], [], -1);
        return $entryArray;
    }

    /**
     * Summary of getEntryArray
     * @param mixed $query
     * @param mixed $params
     * @param mixed $n
     * @return array{0: EntryBook[], 1: integer}
     */
    public function getEntryArray($query, $params, $n)
    {
        $filter = new Filter($this->request, $params, "books", $this->databaseId);
        $filterString = $filter->getFilterString();
        $params = $filter->getQueryParams();

        if (isset($this->orderBy) && $this->orderBy !== Book::SQL_SORT) {
            if (str_contains($query, 'order by')) {
                $query = preg_replace('/\s+order\s+by\s+[\w.]+(\s+(asc|desc)|)\s*/i', ' order by ' . $this->getOrderBy() . ' ', $query);
            } else {
                $query .= ' order by ' . $this->getOrderBy() . ' ';
            }
        }

        /** @var integer $totalNumber */
        /** @var \PDOStatement $result */
        [$totalNumber, $result] = parent::executeQuery($query, Book::getBookColumns(), $filterString, $params, $n, $this->databaseId, $this->numberPerPage);

        $entryArray = [];
        while ($post = $result->fetchObject()) {
            $book = new Book($post, $this->databaseId);
            array_push($entryArray, $book->getEntry());
        }
        return [$entryArray, $totalNumber];
    }
}
