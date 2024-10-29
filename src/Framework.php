<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops;

use SebLucas\Cops\Output\Response;

/**
 * Minimal Framework
 */
class Framework
{
    /** @var array<string, mixed> */
    protected static $handlers = [
        "index" => Handlers\HtmlHandler::class,
        "feed" => Handlers\FeedHandler::class,
        "json" => Handlers\JsonHandler::class,
        "fetch" => Handlers\FetchHandler::class,
        "read" => Handlers\ReadHandler::class,
        "epubfs" => Handlers\EpubFsHandler::class,
        "restapi" => Handlers\RestApiHandler::class,
        "check" => Handlers\CheckHandler::class,
        "opds" => Handlers\OpdsHandler::class,
        "loader" => Handlers\LoaderHandler::class,
        "zipper" => Handlers\ZipperHandler::class,
        "calres" => Handlers\CalResHandler::class,
        "zipfs" => Handlers\ZipFsHandler::class,
        "mail" => Handlers\MailHandler::class,
        "graphql" => Handlers\GraphQLHandler::class,
        "tables" => Handlers\TableHandler::class,
        "error" => Handlers\ErrorHandler::class,
    ];
    /** @var array<mixed> */
    protected static $middlewares = [];

    /**
     * Single request runner with optional handler name
     * @param string $name
     * @return void
     */
    public static function run($name = 'index')
    {
        $request = static::getRequest();
        if ($request->invalid) {
            $name = 'error';
            $handler = Framework::getHandler($name);
            $response = $handler->handle($request);
            if ($response instanceof Response) {
                //$response->prepare($request);
                $response->send();
            }
            return;
        }

        // route to the right handler if needed
        $name = $request->getHandler();

        // special case for json requests here
        if ($name == 'index' && $request->isJson()) {
            $name = 'json';
        }
        $handler = Framework::getHandler($name);
        if (empty(static::$middlewares)) {
            $response = $handler->handle($request);
            if ($response instanceof Response) {
                //$response->prepare($request);
                $response->send();
            }
            return;
        }
        // @see https://www.php-fig.org/psr/psr-15/meta/#queue-based-request-handler
        $queue = new Handlers\QueueBasedHandler($handler);
        foreach (static::$middlewares as $middleware) {
            $queue->add(new $middleware());
        }
        $response = $queue->handle($request);
        if ($response instanceof Response) {
            //$response->prepare($request);
            $response->send();
        }
    }

    /**
     * Get request instance
     * @return Input\Request
     */
    public static function getRequest()
    {
        // initialize routes if needed
        static::init();
        // when using Apache .htaccess redirect
        if (empty($_SERVER['PATH_INFO']) && !empty($_SERVER['REDIRECT_PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = $_SERVER['REDIRECT_PATH_INFO'];
        }
        return new Input\Request();
    }

    /**
     * Initialize framework
     * @return void
     */
    public static function init()
    {
        static::addRoutes();
    }

    /**
     * Add routes for all handlers
     * @return void
     */
    public static function addRoutes()
    {
        if (Input\Route::count() > 0) {
            return;
        }
        foreach (static::$handlers as $name => $handler) {
            Input\Route::addRoutes($handler::getRoutes());
        }
    }

    /**
     * Get handler instance based on name
     * @param string $name
     * @param mixed $args
     * @return mixed
     */
    public static function getHandler($name, ...$args)
    {
        return new static::$handlers[$name](...$args);
    }
}
