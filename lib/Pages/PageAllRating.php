<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\Book;
use SebLucas\Cops\Calibre\Rating;

class PageAllRating extends Page
{
    public function InitializeContent()
    {
        global $config;
        $this->idPage = Rating::PAGE_ID;
        $this->title = localize("ratings.title");
        $this->entryArray = Rating::getAllRatings($this->getDatabaseId());
        if (in_array("rating", $config['cops_show_not_set_filter'])) {
            $instance = new Rating((object)['id' => 0, 'name' => 0], $this->getDatabaseId());
            [$result,] = Book::getBooksWithoutRating(-1, $this->getDatabaseId());
            array_push($this->entryArray, $instance->getEntry(count($result)));
        }
    }
}
