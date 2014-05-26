<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestClientBundle\Hypermedia;

use Tms\Bundle\RestClientBundle\Iterator\HypermediaCollectionIterator;

/**
 * HypermediaCollection
 */
class HypermediaCollection extends AbstractHypermedia implements \IteratorAggregate
{
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new HypermediaCollectionIterator($this);
    }

    /**
     * Follow the nextPage link
     * 
     */
    public function nextPage()
    {
        $this->followLink('nextPage');
    }

    /**
     * Follow the previousPage link
     * 
     */
    public function previousPage()
    {
        $this->followLink('previousPage');
    }

    /**
     * Follow the lastPage link
     * 
     */
    public function lastPage()
    {
        $this->followLink('lastPage');
    }

    /**
     * Follow the firstPage link
     * 
     */
    public function firstPage()
    {
        $this->followLink('firstPage');
    }

    /**
     * Follow a specific page number link
     * 
     * @param integer $page
     * 
     */
    public function page($page)
    {
        return $this->followLink(
            'self', 
            array('page' => $page)
        );
    }

    /**
     * Follow an url to retrieve new hypermedia object
     * 
     * @param string  $name
     * @param string  $params
     * @return HypermediaCollection
     */
    public function followLink($name, $params = array())
    {
        return new HypermediaCollection(
            $this->crawler->get($this->getLinkUrl($name), $params)
        );
    }
}
