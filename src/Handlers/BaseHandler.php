<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Handlers;

use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Input\Route;
use SebLucas\Cops\Output\Response;

/**
 * Summary of BaseHandler
 */
abstract class BaseHandler
{
    public const PARAM = Route::HANDLER_PARAM;
    public const HANDLER = "";

    /**
     * @return array<string, mixed>
     */
    public static function getRoutes()
    {
        return [];
    }

    /**
     * Summary of getLink
     * @param array<mixed> $params
     * @return string
     */
    public static function getLink($params = [])
    {
        return Route::link(static::HANDLER, null, $params);
    }

    /**
     * Summary of request
     * @param array<mixed> $params
     * @return Request
     */
    public static function request($params = [])
    {
        return Request::build($params, static::HANDLER);
    }

    public function __construct()
    {
        // ...
    }

    /**
     * @param Request $request
     * @return Response|void
     */
    abstract public function handle($request);
}
