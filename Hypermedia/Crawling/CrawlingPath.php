<?php

namespace Tms\Bundle\RestClientBundle\Hypermedia\Crawling;

use Da\ApiClientBundle\Http\Rest\RestApiClientInterface;
use Da\ApiClientBundle\Http\Response;
use Tms\Bundle\RestClientBundle\Hypermedia\HypermediaItem;
use Tms\Bundle\RestClientBundle\Hypermedia\HypermediaCollection;
use Tms\Bundle\RestClientBundle\Factory\HypermediaFactory;
use Tms\Bundle\RestClientBundle\Hypermedia\hydratation\HypermediaHydratationHandlerInterface;

/**
 * CrawlingPath represents a path that a crawler can follow.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class CrawlingPath implements CrawlingPathInterface
{
    /**
     * The hydratation handler.
     *
     * @var HypermediaHydratationHandlerInterface
     */
    protected $hydratationHandler;

    /**
     * The crawler.
     *
     * @var CrawlerInterface
     */
    protected $crawler;

    /**
     * The api client.
     *
     * @var RestApiClientInterface
     */
    protected $apiClient;

    /**
     * Constructor
     *
     * @param HypermediaHydratationHandlerInterface $hydratationHandler The hydratation handler.
     * @param RestApiClientInterface                $apiClient          An api client.
     */
    public function __construct(
        HypermediaHydratationHandlerInterface $hydratationHandler,
        RestApiClientInterface $apiClient
    ) {
        $this->hydratationHandler = $hydratationHandler;
        $this->apiClient = $apiClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setCrawler(CrawlerInterface $crawler)
    {
        $this->hydratationHandler->setCrawler($crawler);
        $this->crawler = $crawler;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpointRoot()
    {
        return $this->apiClient->getEndpointRoot();
    }

    /**
     * {@inheritdoc}
     */
    public function matchPath($path)
    {
        $endpointRootSchemeless = strstr($this->getEndpointRoot(), '://');
        $pathSchemeless = strstr($path, '://');

        return $endpointRootSchemeless === substr($pathSchemeless, 0, strlen($endpointRootSchemeless));
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($path, $param, array $headers = array(), $noCache = false)
    {
        $path = sprintf("%s/%s", $path, $param);

        $result = $this->crawl($path, array(), $headers, $noCache);

        if (is_array($result) || $result instanceof HypermediaItem) {
            return $result;
        }

        $class = get_class($result);

        throw new \LogicException(sprintf(
            'The method "findOne" should return a "\Tms\Bundle\RestClientBundle\Hypermedia\HypermediaItem" or an array, a "%s" given instead.',
            $class ? $class : gettype($result)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function find($path, array $params = array(), array $headers = array(), $noCache = false)
    {
        return $this->crawl($path, $params, $headers, $noCache);
    }

    /**
     * {@inheritdoc}
     */
    public function getPathInfo($path, array $headers = array())
    {
        $path = sprintf("%s/info", $path);

        $hypermedia = $this->crawl($path, array(), $headers);

        if ($hypermedia instanceof HypermediaItem) {
            return $hypermedia;
        }

        $class = get_class($hypermedia);

        throw new \LogicException(sprintf(
            'The method "inquire" returns a "\Tms\Bundle\RestClientBundle\Hypermedia\HypermediaItem", a "%s" given instead.',
            $class ? $class : gettype($hypermedia)
        ));
    }

    /**
     * Magic call.
     */
    public function __call($method, $arguments)
    {
        if ($this->matchMethod($method, 'findOne')) {
            $path = $this->retrievePathFromMethodName($method, 'findOne');
            $slug = $arguments[0];
            $absolutePath = isset($arguments[1]) ? $arguments[1] : false;

            return $this->findOne($path, $slug, $absolutePath);
        } else if ($this->matchMethod($method, 'find')) {
            $path = $this->retrievePathFromMethodName($method, 'find');
            $args = isset($arguments[0]) ? $arguments[0] : array();
            $absolutePath = isset($arguments[1]) ? $arguments[1] : false;

            return $this->find($path, $args, $absolutePath);
        } else if ($this->matchMethod($method, 'inquire')) {
            $path = $this->retrievePathFromMethodName($method, 'inquire');
            $absolutePath = isset($arguments[0]) ? $arguments[0] : false;

            return $this->inquire($path, $absolutePath);
        }

        throw new \LogicException(sprintf(
            'The method "%s" is not defined.',
            $method
        ));
    }

    /**
     * Whether or not a method is matching a pattern.
     *
     * @param string $method  The name of the method.
     * @param string $pattern The pattern to match.
     *
     * @return boolean True if the method match, false otherwise.
     */
    private function matchMethod($method, $pattern)
    {
        return substr($method, 0, strlen($pattern)) === $pattern;
    }

    /**
     * Retrieve a path from a method name.
     *
     * @param string $method  The name of the method.
     * @param string $pattern The pattern of the method.
     *
     * @return string The path.
     */
    private function retrievePathFromMethodName($method, $pattern)
    {
        $method = substr($method, 0, strlen($pattern));

        return strtolower(preg_replace('/([A-Z])/', '_$n', $method));
    }

    /**
     * {@inheritdoc}
     */
    public function crawl($path, array $params = array(), array $headers = array(), $noCache = false)
    {
        if ($path[0] !== '/') {
            $path = sprintf('/%s', $path);
        }

        $result = $this
            ->apiClient
            ->get($path, $params, $headers, $noCache, false)
            ->getContent(true)
        ;

        if (is_array($result) && isset($result['metadata'])) {
            $result = $this->hydratationHandler->hydrate($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($path, $method, array $params = array(), array $headers = array())
    {
        $class = new \ReflectionClass($this->apiClient);

        $method = strtolower($method);

        if (!$class->hasMethod($method)) {
            throw new \LogicException(sprintf(
                'The HTTP method "%s" does not exist or has not been implemented.',
                $method
            ));
        }

        if ($path[0] !== '/') {
            $path = sprintf('/%s', $path);
        }

        $result = $this
            ->apiClient
            ->$method($path, $params, $headers)
            ->getContent(true)
        ;

        if (is_array($result) && isset($result['metadata'])) {
            $result = $this->hydratationHandler->hydrate($result);
        }

        return $result;
    }
}
