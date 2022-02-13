<?php


namespace Brace\Body;


use Brace\Core\Base\BraceAbstractMiddleware;
use Phore\Di\Container\DiContainer;
use Phore\Di\Container\Producer\DiProducer;
use Phore\Hydrator\Ex\InvalidStructureException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyMiddleware extends BraceAbstractMiddleware
{

    /**
     * @var ServerRequestInterface
     */
    public $request;

    public function buildBody(DiContainer $container, array $optParams = [], \ReflectionClass $class = null, bool $isArray = false) {
        $request = $this->request;
        $contentType = $request->getHeader("Content-type")[0] ?? null;

        if (in_array($contentType, ["application/x-www-form-urlencoded", "multipart/form-data"])) {
            $arrayData = $request->getParsedBody();
        } elseif (in_array ($contentType, ["application/json", "text/json"])) {
            $arrayData = phore_json_decode($request->getBody()->getContents());
        } else {
            if ($class === null && $isArray === false)
                return $request->getBody()->getContents();
            throw new \InvalidArgumentException("Invalid input content type: '$contentType'");
        }

        if ($isArray === true) {
            return (array)$arrayData;
        }
        if ($class !== null) {
            return phore_hydrate($arrayData, $class->getName());
        }
        return $request->getBody()->getContents();
    }

    public function buildQuery(DiContainer $container, array $optParams = [], \ReflectionClass $class = null, bool $isArray = false) {
        $request = $this->request;
        if ($isArray === true) {
            return (array)$request->getQueryParams();
        }
        if ($class !== null) {
            try {
                return phore_hydrate($this->request->getQueryParams(), $class->getName());

            } catch (InvalidStructureException $e) {
                throw new \InvalidArgumentException("Invalid/Missing query parameters: " . $e->getMessage(), 0, $e);
            }
        }
        return $request->getUri()->getQuery();
    }



    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->request = $request;
        $this->app->define("body", new DiProducer([$this, "buildBody"]));
        $this->app->define("query", new DiProducer([$this, "buildQuery"]));
        return $handler->handle($request);
    }
}
