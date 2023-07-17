<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Model;

use function SebLucas\Cops\Request\getURLParam;

use const SebLucas\Cops\Config\COPS_DB_PARAM;
use const SebLucas\Cops\Config\COPS_ENDPOINTS;

class Link
{
    public const OPDS_THUMBNAIL_TYPE = "http://opds-spec.org/image/thumbnail";
    public const OPDS_IMAGE_TYPE = "http://opds-spec.org/image";
    public const OPDS_ACQUISITION_TYPE = "http://opds-spec.org/acquisition";
    public const OPDS_NAVIGATION_TYPE = "application/atom+xml;profile=opds-catalog;kind=navigation";
    public const OPDS_PAGING_TYPE = "application/atom+xml;profile=opds-catalog;kind=acquisition";

    public static $endpoint = COPS_ENDPOINTS["index"];
    public $href;
    public $type;
    public $rel;
    public $title;
    public $facetGroup;
    public $activeFacet;

    public function __construct($phref, $ptype, $prel = null, $ptitle = null, $pfacetGroup = null, $pactiveFacet = false)
    {
        $this->href = $phref;
        $this->type = $ptype;
        $this->rel = $prel;
        $this->title = $ptitle;
        $this->facetGroup = $pfacetGroup;
        $this->activeFacet = $pactiveFacet;
    }

    public function hrefXhtml()
    {
        return $this->href;
    }

    public static function getScriptName()
    {
        $parts = explode('/', $_SERVER["SCRIPT_NAME"] ??  "/" . self::$endpoint);
        return $parts[count($parts) - 1];
    }

    public static function getEndpointURL($endpoint = "index", $params = null, $database = null)
    {
        $database ??= getURLParam(COPS_DB_PARAM);
        if (!empty($database)) {
            $params ??= [];
            $params[COPS_DB_PARAM] = $database;
        }
        if (!empty($params)) {
            return COPS_ENDPOINTS[$endpoint] . "?" . http_build_query($params);
        }
        return COPS_ENDPOINTS[$endpoint];
    }
}