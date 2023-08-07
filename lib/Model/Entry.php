<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Model;

use SebLucas\Cops\Output\Format;
use SebLucas\Cops\Pages\Page;

class Entry
{
    public $title;
    public $id;
    public $content;
    public $numberOfElement;
    public $contentType;
    /** @var Link[] */
    public $linkArray;
    public $localUpdated;
    public $className;
    private static $updated = null;
    protected $databaseId;

    public static $icons = [
        Page::ALL_AUTHORS_ID             => 'images/author.png',
        Page::ALL_SERIES_ID              => 'images/serie.png',
        Page::ALL_RECENT_BOOKS_ID        => 'images/recent.png',
        Page::ALL_TAGS_ID                => 'images/tag.png',
        Page::ALL_LANGUAGES_ID           => 'images/language.png',
        Page::ALL_CUSTOMS_ID             => 'images/custom.png',
        Page::ALL_RATING_ID              => 'images/rating.png',
        "cops:books$"                    => 'images/allbook.png',
        "cops:books:letter"              => 'images/allbook.png',
        Page::ALL_PUBLISHERS_ID          => 'images/publisher.png',
    ];

    public function __construct($ptitle, $pid, $pcontent, $pcontentType = "text", $plinkArray = [], $database = null, $pclass = "", $pcount = 0)
    {
        global $config;
        $this->title = $ptitle;
        $this->id = $pid;
        $this->content = $pcontent;
        $this->contentType = $pcontentType;
        $this->linkArray = $plinkArray;
        $this->className = $pclass;
        $this->numberOfElement = $pcount;

        if ($config['cops_show_icons'] == 1) {
            foreach (self::$icons as $reg => $image) {
                if (preg_match("/" . $reg . "/", $pid)) {
                    array_push($this->linkArray, new Link(Format::addVersion($image), "image/png", Link::OPDS_THUMBNAIL_TYPE));
                    break;
                }
            }
        }

        if (!is_null($database)) {
            $this->id = str_replace("cops:", "cops:" . $database . ":", $this->id);
        }
    }

    public function getUpdatedTime()
    {
        if (!is_null($this->localUpdated)) {
            return date(DATE_ATOM, $this->localUpdated);
        }
        if (is_null(self::$updated)) {
            self::$updated = time();
        }
        return date(DATE_ATOM, self::$updated);
    }

    public function getNavLink($extraUri = "")
    {
        foreach ($this->linkArray as $link) {
            /** @var $link LinkNavigation */

            if ($link->type != Link::OPDS_NAVIGATION_TYPE) {
                continue;
            }

            return $link->hrefXhtml() . $extraUri;
        }
        return "#";
    }

    public function getThumbnail()
    {
        foreach ($this->linkArray as $link) {
            /** @var $link LinkNavigation */

            if ($link->rel == Link::OPDS_THUMBNAIL_TYPE) {
                return $link->hrefXhtml();
            }
        }
        return null;
    }

    public function getImage()
    {
        foreach ($this->linkArray as $link) {
            /** @var $link LinkNavigation */

            if ($link->rel == Link::OPDS_IMAGE_TYPE) {
                return $link->hrefXhtml();
            }
        }
        return null;
    }
}
