<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Model;

class Link
{
    //public const OPDS_THUMBNAIL_TYPE = "http://opds-spec.org/image/thumbnail";
    //public const OPDS_IMAGE_TYPE = "http://opds-spec.org/image";
    //public const OPDS_ACQUISITION_TYPE = "http://opds-spec.org/acquisition";
    //public const OPDS_NAVIGATION_FEED = "application/atom+xml;profile=opds-catalog;kind=navigation";
    //public const OPDS_ACQUISITION_FEED = "application/atom+xml;profile=opds-catalog;kind=acquisition";

    public string|\Closure $href;
    public string $type;
    /** @var ?string */
    public $rel;
    /** @var ?string */
    public $title;

    /**
     * Summary of __construct
     * @param string|\Closure $href uri or closure including the endpoint
     * @param string $type link type in the OPDS catalog
     * @param ?string $rel relation in the OPDS catalog
     * @param ?string $title title in the OPDS catalog and elsewhere
     */
    public function __construct($href, $type, $rel = null, $title = null)
    {
        $this->href = $href;
        $this->type = $type;
        $this->rel = $rel;
        $this->title = $title;
    }

    /**
     * Summary of getUri
     * @return string
     */
    public function getUri()
    {
        if ($this->href instanceof \Closure) {
            $this->href = ($this->href)();
        }
        // Link()->href includes the endpoint here
        return $this->href;
    }
}
