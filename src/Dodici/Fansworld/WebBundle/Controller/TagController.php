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
    
    
    /**
     *  get params (all optional):
     *   - text (partial match)
     *   - page
     *  @Route("/ajax/getUsedInVideos/", name="tag_ajaxgetusedinvideos")
     */
    public function ajaxTagsUsedInVideos()
    {
        $request = $this->getRequest();
        
        $filtertype    = $request->get('filtertype');
        $videocategory = $request->get('videocategory');
        $limit         = null; 
        $offset       = null;
         
        
        $tags = $this->get('tagger')->usedInVideos('popular');
        
        $response = array('tags' => $tags);
        /*
        foreach ($tags as $tag) {
            $response['tags'][] = array(
                    'id' => $tag['id'],
                    'title' => $tag['title'],
                    'type' => $tag['type'],
            );
        }
        */
        return $this->jsonResponse($response);
    }
    
	/**
     *  get params:
     *   - text
     *   - limit
     *  @Route("/ajax/matchall/", name="tag_ajaxmatch")
     */
    public function matchAll()
    {
        $request = $this->getRequest();
    	$text = $request->get('text');
    	$limit = $request->get('limit', 4);
    	
    	$tags = $this->getRepository('Tag')->matchAll($text, $this->getUser(), $limit);
        
        $response = array();
        
        $c = 0;
        foreach ($tags as $type => $ents) {
            foreach ($ents as $ent) {
                $r = array(
                    'id' => $c,
                    'label' => (string) $ent,
                    'value' => (string) $ent,
                );
                
                $entjson = array(
                    'id' => $ent->getId(),
                    'type' => $type
                );
                if (property_exists($ent, 'slug')) $entjson['slug'] = $ent->getSlug();
                if (property_exists($ent, 'username')) $entjson['username'] = $ent->getUsername();
                $r['result'] = $entjson;
                $response[] = $r;
                $c++;
            }
        }
        
        return $this->jsonResponse($response);
    }
    
}
