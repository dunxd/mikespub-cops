<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (https://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Handlers;

use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Output\FileResponse;
use SebLucas\Cops\Output\Response;
use SebLucas\Cops\Output\Zipper;

/**
 * Download all books for a page, series or author by format (epub, mobi, any, ...)
 * URL format: index.php/zipper?page={page}&type={type}&id={id}
 */
class ZipperHandler extends BaseHandler
{
    public const HANDLER = "zipper";
    public const PREFIX = "/zipper";
    public const PARAMLIST = ["page", "type", "id"];

    public static function getRoutes()
    {
        return [
            "zipper-page-id-type" => ["/zipper/{page}/{id}/{type}.zip"],
            "zipper-page-type" => ["/zipper/{page}/{type}.zip"],
        ];
    }

    public function handle($request)
    {
        if (empty(Config::get('download_page'))) {
            return Response::sendError($request, 'Downloads by page are disabled in config');
        }
        if (Config::get('fetch_protect') == '1') {
            $session = $this->getContext()->getSession();
            $session->start();
            $connected = $session->get('connected');
            if (!isset($connected)) {
                return Response::notFound($request);
            }
        }

        // create empty file response to start with!?
        $response = new FileResponse();

        $zipper = new Zipper($request, $response);

        if ($zipper->isValidForDownload()) {
            $sendHeaders = headers_sent() ? false : true;
            // disable nginx buffering by default
            if ($sendHeaders) {
                header('X-Accel-Buffering: no');
            }
            return $zipper->download(null, $sendHeaders);
        }
        return Response::sendError($request, "Invalid download: " . $zipper->getMessage());
    }
}
