<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Model;

use SebLucas\Cops\Output\Format;

class LinkNavigation extends Link
{
    public function __construct($phref, $prel = null, $ptitle = null, $database = null)
    {
        parent::__construct($phref, Link::OPDS_NAVIGATION_TYPE, $prel, $ptitle);
        $this->href = Format::addDatabaseParam($this->href, $database);
        if (!preg_match("#^\?(.*)#", $this->href) && !empty($this->href)) {
            $this->href = "?" . $this->href;
        }
        if (preg_match("/(bookdetail|getJSON).php/", parent::getScriptName())) {
            $this->href = parent::$endpoint . $this->href;
        } else {
            $this->href = parent::getScriptName() . $this->href;
        }
    }
}
