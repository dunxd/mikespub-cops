<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Model;

use SebLucas\Cops\Input\Config;

class Link
{
    public const OPDS_THUMBNAIL_TYPE = "http://opds-spec.org/image/thumbnail";
    public const OPDS_IMAGE_TYPE = "http://opds-spec.org/image";
    public const OPDS_ACQUISITION_TYPE = "http://opds-spec.org/acquisition";
    public const OPDS_NAVIGATION_TYPE = "application/atom+xml;profile=opds-catalog;kind=navigation";
    public const OPDS_PAGING_TYPE = "application/atom+xml;profile=opds-catalog;kind=acquisition";

    public static $endpoint = Config::ENDPOINT["index"];
    public $href;
    public $type;
    public $rel;
    public $title;

    /**
     * Summary of __construct
     * @param string $phref uri including the endpoint for images, books etc.
     * @param string $ptype link type in the OPDS catalog
     * @param ?string $prel relation in the OPDS catalog
     * @param ?string $ptitle title in the OPDS catalog and elsewhere
     */
    public function __construct($phref, $ptype, $prel = null, $ptitle = null)
    {
        $this->href = $phref;
        $this->type = $ptype;
        $this->rel = $prel;
        $this->title = $ptitle;
    }

    public function hrefXhtml($endpoint = '')
    {
        // Link()->href includes the endpoint here
        return $this->href;
    }
}
