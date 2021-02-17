<?php


namespace Brace\Body;


use Brace\Core\Base\BraceAbstractMiddleware;
use Phore\Di\Container\DiContainer;
use Phore\Di\Container\Producer\DiProducer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyMiddleware extends BraceAbstractMiddleware
{


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->app->define("body", new DiProducer(function (DiContainer $container, array $optParams = [], \ReflectionClass $class = null, bool $isArray = false) use ($request) {
            if ($isArray === true) {
                return (array)$request->getParsedBody();
            }
            if ($class !== null) {
                return phore_hydrate((array)$request->getParsedBody(), $class->getName());
            }
            return $request->getBody()->getContents();
        }));
        return $handler->handle($request);
    }
}