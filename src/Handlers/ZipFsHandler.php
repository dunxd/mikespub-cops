<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Handlers;

use SebLucas\Cops\Handlers\TestHandler;
use SebLucas\Cops\Output\EPubReader;
use SebLucas\Cops\Output\Response;
use Exception;

/**
 * Handle Epub filesystem for epubjs-reader
 * URL format: index.php/zipfs/{db}/{data}/{comp}
 */
class ZipFsHandler extends BaseHandler
{
    public const HANDLER = "zipfs";
    public const PREFIX = "/zipfs";
    public const PARAMLIST = ["db", "data", "comp"];

    public static function getRoutes()
    {
        // support custom pattern for route placeholders - see nikic/fast-route
        return [
            "zipfs" => ["/zipfs/{db:\d+}/{data:\d+}/{comp:.+}"],
        ];
    }

    public function handle($request)
    {
        if (php_sapi_name() === 'cli' && $request->getHandler() !== TestHandler::class) {
            return;
        }

        //$database = $request->getId('db');
        $idData = $request->getId('data');
        if (empty($idData)) {
            // this will call exit()
            Response::notFound($request);
        }
        $component = $request->get('comp');
        if (empty($component)) {
            // this will call exit()
            Response::notFound($request);
        }
        $database = $request->database();

        // create empty response to start with!?
        $response = new Response();

        $reader = new EPubReader($request, $response);

        try {
            return $reader->sendZipContent($idData, $component, $database);

        } catch (Exception $e) {
            error_log($e);
            // this will call exit()
            Response::sendError($request, $e->getMessage());
        }
    }
}
