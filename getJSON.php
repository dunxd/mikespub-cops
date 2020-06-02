<?php
/**
 * COPS (Calibre OPDS PHP Server) HTML main script
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 *
 */

require('config.php');
require('base.php');

initURLParam();

header('Content-Type:application/json;charset=utf-8');

echo json_encode(JSONRenderer::getJson());

