<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\BaseList;
use SebLucas\Cops\Calibre\BookList;
use SebLucas\Cops\Calibre\Serie;

class PageAllSeries extends Page
{
    protected $className = Serie::class;

    public function InitializeContent()
    {
        $this->getEntries();
        $this->idPage = Serie::PAGE_ID;
        $this->title = localize("series.title");
    }

    public function getEntries()
    {
        global $config;
        $baselist = new BaseList($this->className, $this->request);
        $this->entryArray = $baselist->getRequestEntries($this->n);
        $this->totalNumber = $baselist->countRequestEntries();
        $this->sorted = $baselist->orderBy;
        if ((!$this->isPaginated() || $this->n == $this->getMaxPage()) && in_array("series", $config['cops_show_not_set_filter'])) {
            array_push($this->entryArray, $baselist->getWithoutEntry());
        }
    }
}
