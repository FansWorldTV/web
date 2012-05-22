<?php

namespace Dodici\Fansworld\WebBundle\Model;

/**
 * Element that can be searched by the Search service
 */
interface SearchableInterface
{
    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle();
    
	/**
     * Get content
     *
     * @return text 
     */
    public function getContent();
}
