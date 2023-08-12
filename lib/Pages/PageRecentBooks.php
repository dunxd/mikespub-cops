<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\BookList;

class PageRecentBooks extends Page
{
    //protected $className = Book::class;

    public function InitializeContent()
    {
        $this->getEntries();
        $this->idPage = parent::ALL_RECENT_BOOKS_ID;
        $this->title = localize("recent.title");
    }

    public function getEntries()
    {
        $booklist = new BookList($this->request);
        $this->entryArray = $booklist->getAllRecentBooks();
        $this->sorted = $booklist->orderBy ?? "timestamp desc";
    }
}
