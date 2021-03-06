<?php

namespace Tms\Bundle\RestClientBundle\Hypermedia\Hydratation;

use Tms\Bundle\RestClientBundle\Hypermedia\Constants;
use Tms\Bundle\RestClientBundle\Hypermedia\Crawling\CrawlerInterface;

/**
 * HypermediaHydratationHandler is a basic implementation of
 * an hydratation handler for the hypermedia.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class HypermediaHydratationHandler implements HypermediaHydratationHandlerInterface
{
    /**
     * The crawler.
     *
     * @var CrawlerInterface
     */
    protected $crawler;

    /**
     * The hydrators.
     *
     * @var array
     */
    protected $hydrators = array();

    /**
     * {@inheritdoc}
     */
    public function setCrawler(CrawlerInterface $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * Set a hydrator.
     *
     * @param string            $id       The id of the hydrator.
     * @param HydratorInterface $hydrator The hydrator.
     */
    public function setHydrator($id, HypermediaHydratorInterface $hydrator)
    {
        $hydrator->setHydratationHandler($this);

        $this->hydrators[$id] = $hydrator;
    }

    /**
     * Retrieve an hydrator.
     *
     * @param string $hydratorId The id of the hydrator.
     *
     * @return HypermediaHydratorInterface The hydrator.
     */
    protected function getHydrator($hydratorId)
    {
        if (!isset($this->hydrators[$hydratorId])) {
            throw new \LogicException(sprintf(
                'The hydrator "%s" is not defined.',
                $hydratorId
            ));
        }

        return $this->hydrators[$hydratorId];
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(array $hypermedia)
    {
        $hydratorId = "tms_rest.array";

        if (isset($hypermedia['metadata'][Constants::SERIALIZER_CONTEXT_GROUP_NAME])) {
            $hydratorId = $hypermedia['metadata'][Constants::SERIALIZER_CONTEXT_GROUP_NAME];
        }

        $hypermedia = $this->getHydrator($hydratorId)->hydrate($hypermedia);
        $hypermedia->setCrawler($this->crawler);

        return $hypermedia;
    }
}
