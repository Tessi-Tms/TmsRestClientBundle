<?php

namespace Tms\Bundle\RestClientBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @author Thomas Prelot <thomas.prelot@gmail.com>
 *
 * @Route("/rest-client/browser")
 */
class BrowserController extends ContainerAware
{
    /**
     * Index.
     *
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $crawler = $this->container->get('tms_rest_client.hypermedia.crawler');

        return array('crawlingPaths' => $crawler->getCrawlingPathIds());
    }

    /**
     * Go.
     *
     * @Route("/go/{crawling_path}")
     * @Template()
     */
    public function goAction($crawling_path)
    {
        // Check the existence of the crawling path.
        $this->container->get('tms_rest_client.hypermedia.crawler')
            ->go($crawling_path)
        ;

        return array('crawlingPath' => $crawling_path);
    }

    /**
     * Find one.
     *
     * @Route("/find-one/{crawling_path}")
     * @Template("TmsRestClientBundle:Browser:crawl.html.twig")
     */
    public function findOneAction($crawling_path)
    {
        $request = $this->container->get('request');
        $queryParameters = $request->query;

        $path = $queryParameters->get('path');
        $slug = $queryParameters->get('slug');

        $hypermedia = $this->container->get('tms_rest_client.hypermedia.crawler')
            ->go($crawling_path)
            ->findOne($path, $slug)
        ;

        return array(
            'crawlingPath' => $crawling_path,
            'hypermedia' => $hypermedia
        );
    }

    /**
     * Find.
     *
     * @Route("/find/{crawling_path}")
     * @Template("TmsRestClientBundle:Browser:crawl.html.twig")
     */
    public function findAction($crawling_path)
    {
        $request = $this->container->get('request');
        $queryParameters = $request->query;

        $path = $queryParameters->get('path');
        $params = $queryParameters->get('params', '');
        if (!$params) {
            $params = array();
        } else {
            $params = json_decode($params, true);
        }

        if (null === $params) {
            $content = $this->container->get('templating')->render(
                'TmsRestClientBundle:Browser:error.html.twig',
                array(
                    'error' => 'Bad JSON for find parameters.'
                )
            );

            return new Response($content);
        }

        $hypermedia = $this->container->get('tms_rest_client.hypermedia.crawler')
            ->go($crawling_path)
            ->find($path, $params)
        ;

        return array(
            'crawlingPath' => $crawling_path,
            'hypermedia' => $hypermedia
        );
    }

    /**
     * Inquire.
     *
     * @Route("/inquire/{crawling_path}")
     * @Template("TmsRestClientBundle:Browser:crawl.html.twig")
     */
    public function inquireAction($crawling_path)
    {
        $request = $this->container->get('request');
        $path = $request->query->get('path');

        $hypermedia = $this->container->get('tms_rest_client.hypermedia.crawler')
            ->go($crawling_path)
            ->inquire($path)
        ;

        return array(
            'crawlingPath' => $crawling_path,
            'hypermedia' => $hypermedia
        );
    }

    /**
     * Crawl.
     *
     * @Route("/crawl")
     * @Template("TmsRestClientBundle:Browser:crawl.html.twig")
     */
    public function crawlAction()
    {
        $request = $this->container->get('request');
        $url = $request->query->get('url');

        $hypermedia = $this->container->get('tms_rest_client.hypermedia.crawler')
            ->crawl($url)
        ;

        return array('hypermedia' => $hypermedia);
    }
}