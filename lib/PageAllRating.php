<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\Rating;

class PageAllRating extends Page
{
    public function InitializeContent()
    {
        $this->title = localize("ratings.title");
        $this->entryArray = Rating::getAllRatings();
        $this->idPage = Rating::ALL_RATING_ID;
    }
}
