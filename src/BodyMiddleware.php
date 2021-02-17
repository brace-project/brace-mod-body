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

            $contentType = $request->getHeader("Content-type")[0];
            if (in_array($contentType, ["application/x-www-form-urlencoded", "multipart/form-data"])) {
                $arrayData = $request->getParsedBody();
            } elseif (in_array ($contentType, ["application/json", "text/json"])) {
                $arrayData = phore_json_decode($request->getBody()->getContents());
            } else {
                throw new \InvalidArgumentException("Invalid input content type: '$contentType'");
            }
            
            if ($isArray === true) {
                return (array)$arrayData;
            }
            if ($class !== null) {
                return phore_hydrate($arrayData, $class->getName());
            }
            return $request->getBody()->getContents();
        }));
        return $handler->handle($request);
    }
}