<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestClientBundle\Crawler;

use Da\ApiClientBundle\Http\Rest\RestApiClientInterface;
use Da\ApiClientBundle\Http\Response;
use Tms\Bundle\RestClientBundle\Hypermedia\HypermediaItem;
use Tms\Bundle\RestClientBundle\Hypermedia\HypermediaCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * HypermediaCrawler
 */
class HypermediaCrawlerHandler
{
    protected $crawlers;

    public function __construct()
    {
        $this->crawlers = array();
    }

    /**
     * Set a crawler
     * 
     * @param $crawler HypermediaCrawler
     * @param string $crawlerServiceId
     * 
     * @return $this
     */
    public function setCrawler(HypermediaCrawler $crawler, $crawlerServiceId) 
    {
        $this->crawlers[$crawlerServiceId] = $crawler;

        return $this;
    }

    /**
     * Get a crawler
     * 
     * @param string $crawlerServiceId
     * 
     * @return HypermediaCrawler
     */
    public function getCrawler($crawlerServiceId)
    {
        return $this->crawlers[$crawlerServiceId];
    }

    /**
     * Guess a crawler by path
     * 
     * @param string $path
     * 
     * @return HypermediaCrawler
     * @throws NotFoundHttpException
     */
    public function guessCrawler($path)
    {
        foreach($this->crawlers as $crawler) {
            if($crawler->matchPath($path)) {
                $crawler->setCrawlerHandler($this);

                return $crawler;
            }
        }

        throw new NotFoundHttpException(sprintf(
            "No crawler matching '%s' path found.", $path
        ));
    }
}