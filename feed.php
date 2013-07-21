<?php
/**
 * COPS (Calibre OPDS PHP Server) main script
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 *
 */

    require_once ("config.php");
    require_once ("base.php");
    require_once ("author.php");
    require_once ("serie.php");
    require_once ("tag.php");
    require_once ("book.php");
    require_once ("OPDS_renderer.php");
    
    header ("Content-Type:application/xml");
    $page = getURLParam ("page", Base::PAGE_INDEX);
    $query = getURLParam ("query");
    $n = getURLParam ("n", "1");
    if ($query)
        $page = Base::PAGE_OPENSEARCH_QUERY;
    $qid = getURLParam ("id");
    
    $OPDSRender = new OPDSRenderer ();
    
    // Clean output buffer : mandatory for nginx / php-fpm on Wheezy
    ob_end_clean();
    
    switch ($page) {
        case Base::PAGE_OPENSEARCH :
            echo $OPDSRender->getOpenSearch ();
            return;
        default:
            $currentPage = Page::getPage ($page, $qid, $query, $n);
            $currentPage->InitializeContent ();
            echo $OPDSRender->render ($currentPage);
            return;
            break;
    }
?>