<?php

/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL v2 or later (https://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Handlers;

use SebLucas\Cops\Input\RequestContext;
use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Output\Response;

/**
 * Basic middleware dispatcher (FIFO queue)
 * This is mainly useful for request middleware, not response middleware,
 * since most COPS request handlers already finish sending the response
 * @see https://www.php-fig.org/psr/psr-15/meta/#queue-based-request-handler
 */
class QueueBasedHandler extends BaseHandler
{
    /** @var BaseHandler */
    protected $handler;
    /** @var array<mixed> */
    protected $queue = [];

    /**
     * Set final request handler
     * @param RequestContext $context
     * @param BaseHandler $handler
     */
    public function __construct($context, $handler)
    {
        $this->setContext($context);
        $this->handler = $handler;
    }

    /**
     * Add middleware to queue (FIFO)
     * @param mixed $middleware
     * @return void
     */
    public function add($middleware)
    {
        $this->queue[] = $middleware;
    }

    /**
     * Process next middleware in queue or call final handler
     * @param Request $request
     * @return Response
     */
    public function handle($request)
    {
        if (empty($this->queue)) {
            // return Response if it's not already sent
            return $this->handler->handle($request);
        }
        // @todo support other __invoke, callable etc. middleware
        $middleware = array_shift($this->queue);
        return $middleware->process($request, $this);
    }
}
