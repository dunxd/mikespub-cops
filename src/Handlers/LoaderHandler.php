<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Handlers;

use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Input\Route;
use SebLucas\Cops\Output\Response;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\App\ExtraActions;

/**
 * Summary of LoaderHandler
 */
class LoaderHandler extends BaseHandler
{
    public const HANDLER = "loader";

    public static function getRoutes()
    {
        return [
            "/loader/{action}/{dbNum:\d+}/{authorId:\w+}/{urlPath:.*}" => [],
            "/loader/{action}/{dbNum:\d+}/{authorId:\w*}" => [],
            "/loader/{action}/{dbNum:\d+}" => [],
            "/loader/{action}/" => [],
            "/loader/{action}" => [],
            "/loader" => [],
        ];
    }

    public function handle($request)
    {
        if (!class_exists('\Marsender\EPubLoader\RequestHandler')) {
            echo 'This handler is available in developer mode only (without --no-dev option):' . "<br/>\n";
            echo '$ composer install -o';
            return;
        }
        // get the global config for epub-loader from config/loader.php
        $gConfig = require dirname(__DIR__, 2) . '/config/loader.php';
        // adapt for use with COPS
        $gConfig['endpoint'] = static::getLink();
        $gConfig['app_name'] = 'COPS Loader';
        $gConfig['version'] = Config::VERSION;
        $gConfig['admin_email'] = '';
        $gConfig['create_db'] = false;
        $gConfig['databases'] = [];

        // specify a cache directory for any Google or Wikidata lookup
        $cacheDir = $gConfig['cache_dir'] ?? dirname(__DIR__, 2) . '/cache';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0o777, true)) {
            echo 'Please make sure the cache directory can be created';
            return;
        }
        if (!is_writable($cacheDir)) {
            echo 'Please make sure the cache directory is writeable';
            return;
        }

        // get the current COPS calibre directories
        $calibreDir = Config::get('calibre_directory');
        if (!is_array($calibreDir)) {
            $calibreDir = ['COPS Database' => $calibreDir];
        }
        foreach ($calibreDir as $name => $path) {
            $gConfig['databases'][] = ['name' => $name, 'db_path' => rtrim((string) $path, '/'), 'epub_path' => '.'];
        }

        $action = $request->get('action');
        $dbNum = $request->getId('dbNum');
        $itemId = $request->get('authorId');
        $urlPath = $request->get('urlPath');

        $urlParams = $request->urlParams;

        // you can define extra actions for your app - see example.php
        $handler = new RequestHandler($gConfig, ExtraActions::class, $cacheDir);
        $result = $handler->request($action, $dbNum, $urlParams, $urlPath);

        if (method_exists($handler, 'isDone')) {
            if ($handler->isDone()) {
                return;
            }
        }

        // handle the result yourself or let epub-loader generate the output
        $result = array_merge($gConfig, $result);
        //$templateDir = 'templates/twigged/loader';  // if you want to use custom templates
        $templateDir = $gConfig['template_dir'] ?? null;
        $template = null;

        $response = new Response('text/html;charset=utf-8');
        return $response->setContent($handler->output($result, $templateDir, $template));
    }
}
