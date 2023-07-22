<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Pages;

use SebLucas\Cops\Calibre\Tag;

class PageAllTags extends Page
{
    public function InitializeContent()
    {
        $this->idPage = Tag::PAGE_ID;
        $this->title = localize("tags.title");
        $this->entryArray = Tag::getAllTags($this->getDatabaseId());
    }
}
