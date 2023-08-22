<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     SenorSmartyPants <senorsmartypants@gmail.com>
 */

namespace SebLucas\Cops\Calibre;

class Identifier
{
    /** @var mixed */
    public $id;
    /** @var mixed */
    public $type;
    public string $formattedType;
    /** @var mixed */
    public $val;
    public string $uri;
    /** @var mixed */
    protected $databaseId;

    /**
     * Summary of __construct
     * @param mixed $post
     * @param mixed $database
     */
    public function __construct($post, $database = null)
    {
        $this->id = $post->id;
        $this->type = strtolower($post->type);
        $this->val = $post->val;
        $this->formatType();
        $this->databaseId = $database;
    }

    /**
     * Summary of formatType
     * @return void
     */
    public function formatType()
    {
        if ($this->type == 'amazon') {
            $this->formattedType = "Amazon";
            $this->uri = sprintf("https://amazon.com/dp/%s", $this->val);
        } elseif ($this->type == "asin") {
            $this->formattedType = $this->type;
            $this->uri = sprintf("https://amazon.com/dp/%s", $this->val);
        } elseif (substr($this->type, 0, 7) == "amazon_") {
            $this->formattedType = sprintf("Amazon.co.%s", substr($this->type, 7));
            $this->uri = sprintf("https://amazon.co.%s/dp/%s", substr($this->type, 7), $this->val);
        } elseif ($this->type == "isbn") {
            $this->formattedType = "ISBN";
            $this->uri = sprintf("https://www.worldcat.org/isbn/%s", $this->val);
        } elseif ($this->type == "doi") {
            $this->formattedType = "DOI";
            $this->uri = sprintf("https://dx.doi.org/%s", $this->val);
        } elseif ($this->type == "douban") {
            $this->formattedType = "Douban";
            $this->uri = sprintf("https://book.douban.com/subject/%s", $this->val);
        } elseif ($this->type == "goodreads") {
            $this->formattedType = "Goodreads";
            $this->uri = sprintf("https://www.goodreads.com/book/show/%s", $this->val);
        } elseif ($this->type == "google") {
            $this->formattedType = "Google Books";
            $this->uri = sprintf("https://books.google.com/books?id=%s", $this->val);
        } elseif ($this->type == "kobo") {
            $this->formattedType = "Kobo";
            $this->uri = sprintf("https://www.kobo.com/ebook/%s", $this->val);
        } elseif ($this->type == "litres") {
            $this->formattedType = "ЛитРес";
            $this->uri = sprintf("https://www.litres.ru/%s", $this->val);
        } elseif ($this->type == "issn") {
            $this->formattedType = "ISSN";
            $this->uri = sprintf("https://portal.issn.org/resource/ISSN/%s", $this->val);
        } elseif ($this->type == "isfdb") {
            $this->formattedType = "ISFDB";
            $this->uri = sprintf("http://www.isfdb.org/cgi-bin/pl.cgi?%s", $this->val);
        } elseif ($this->type == "lubimyczytac") {
            $this->formattedType = "Lubimyczytac";
            $this->uri = sprintf("https://lubimyczytac.pl/ksiazka/%s/ksiazka", $this->val);
        } elseif ($this->type == "url") {
            $this->formattedType = $this->type;
            $this->uri = $this->val;
        } else {
            $this->formattedType = $this->type;
            $this->uri = '';
        }
    }

    /**
     * Summary of getUri
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Summary of getInstancesByBookId
     * @param mixed $bookId
     * @param mixed $database
     * @return array<Identifier>
     */
    public static function getInstancesByBookId($bookId, $database = null)
    {
        $identifiers = [];

        $query = 'select type, val, id
            from identifiers
            where book = ?
            order by type';
        $result = Database::query($query, [$bookId], $database);
        while ($post = $result->fetchObject()) {
            array_push($identifiers, new Identifier($post, $database));
        }
        return $identifiers;
    }
}
