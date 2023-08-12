<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\Author;
use SebLucas\Cops\Calibre\BaseList;

class PageAllAuthorsLetter extends Page
{
    protected $className = Author::class;

    public function InitializeContent()
    {
        $this->getEntries();
        $this->idPage = Author::getEntryIdByLetter($this->idGet);
        $count = $this->totalNumber;
        if ($count == -1) {
            $count = count($this->entryArray);
        }
        $this->title = str_format(localize("splitByLetter.letter"), str_format(localize("authorword", $count), $count), $this->idGet);
        $this->parentTitle = "";  // localize("authors.title");
        $this->parentUri = "?page=".Author::PAGE_ALL;
    }

    public function getEntries()
    {
        $baselist = new BaseList($this->className, $this->request);
        $this->entryArray = $baselist->getEntriesByFirstLetter($this->idGet, $this->n);
        $this->totalNumber = $baselist->countEntriesByFirstLetter($this->idGet);
        $this->sorted = $baselist->orderBy;
    }
}
