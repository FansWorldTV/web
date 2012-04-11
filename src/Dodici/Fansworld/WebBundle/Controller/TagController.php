<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Tag;

/**
 * Interest controller.
 * @Route("/tag")
 */
class TagController extends SiteController
{
	
    /**
     *  get params (all optional):
     *   - text (partial match)
     *   - page
     *  @Route("/ajax/get/", name="tag_ajaxget")
     */
    public function ajaxTags()
    {
        $request = $this->getRequest();
    	$text = $request->get('text');
    	$page = $request->get('page');
    	$limit = null; $offset = null;
    	
    	if ($page !== null) {
    		$page--;
    		$limit = self::LIMIT_AJAX_GET;
    		$offset = $limit * $page;
    	}
        
    	$tags = $this->getRepository('Tag')->matching($text, $limit, $offset);
        
        $response = array();
        foreach ($tags as $tag) {
            $response[] = array(
            	'id' => $tag->getId(),
            	'value' => $tag->getTitle(),
            	'add' => $tag->getTitle(),
            );
        }

        return $this->jsonResponse($response);
    }
    
}
