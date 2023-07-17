<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\Book;

use function SebLucas\Cops\Language\localize;
use function SebLucas\Cops\Request\getCurrentOption;

class PageAllBooks extends Page
{
    public function InitializeContent()
    {
        $this->title = localize("allbooks.title");
        if (getCurrentOption("titles_split_first_letter") == 1) {
            $this->entryArray = Book::getAllBooks();
        } else {
            [$this->entryArray, $this->totalNumber] = Book::getBooks($this->n);
        }
        $this->idPage = Book::PAGE_ID;
    }
}