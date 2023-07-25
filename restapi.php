<?php
/**
 * COPS (Calibre OPDS PHP Server) REST API endpoint
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 *
 */

use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Output\RestApi;

require_once dirname(__FILE__) . '/config.php';
/** @var array $config */

// override splitting authors and books by first letter here?
$config['cops_author_split_first_letter'] = '0';
$config['cops_titles_split_first_letter'] = '0';

$request = new Request();
$apiHandler = new RestApi($request);

header('Content-Type:application/json;charset=utf-8');

echo $apiHandler->getOutput();
