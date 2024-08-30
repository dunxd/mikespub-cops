<?php
/**
 * COPS (Calibre OPDS PHP Server) endpoint for GraphQL (dev only)
 * URL format: graphql.php?db={db} etc.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

use SebLucas\Cops\Framework;

require_once __DIR__ . '/config.php';

Framework::run('graphql');
