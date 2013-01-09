<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * User feeds service
 */
class UserFeed
{
	protected $security_context;
	protected $em;
    protected $user;
    protected $appmedia;
    
    private $images;
    private $imageurls;
    
    function __construct(SecurityContext $security_context, EntityManager $em, $appmedia)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->appmedia = $appmedia;
        $this->user = null;
        $this->images = array();
        $this->imageurls = array();
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }
    
    /**
     * get latest activity for the user watch feed
     * @param User|null $user
     * @param array $filters - 'fans', 'idols', 'teams' possible elements
     * @param array $resulttypes - 'video', 'photo' possible elements
     * @param int $limit (default 10)
     * @param DateTime|null $maxdate
     * @param DateTime|null $mindate
     * @param boolean $parseimages - whether to hydrate images or not
     */
    public function latestActivity(
        $filters = array('fans', 'idols', 'teams'), 
        $resulttype = array('video', 'photo'), 
        $limit = 10,
        $maxdate = null,
        $mindate = null,
        User $user = null,
        $parseimages = true,
        $imageformat = 'big',
        $authorimageformat = 'small_square'
    )
    {
        if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        $items = $this->em->getRepository('ApplicationSonataUserBundle:User')->latestActivity(
            $user, $filters, $resulttype, $limit, $maxdate, $mindate
        );
        
        if ($parseimages) {
            $parsed = array();
            foreach ($items as $item) $parsed[] = $this->parseImages($item, $imageformat, $authorimageformat);
            return $parsed;
        } else {
            return $items;
        }
    }
    
    private function parseImages($item, $imageformat, $authorimageformat) 
    {
        $imageid = $item['imageid'];
        $authorimageid = (isset($item['author']) ? ($item['author']['imageid']) : null);
        
        if ($imageid) {
            $imageurl = $this->getImageUrlByFormat($imageid, $imageformat);
            if ($imageurl) {
                $item['image'] = $imageurl;
            }
        }
        
        if ($authorimageid) {
            $imageurl = $this->getImageUrlByFormat($authorimageid, $authorimageformat);
            if ($imageurl) {
                $item['author']['image'] = $imageurl;
            }
        }
        
        return $item;
    }
    
    private function getImageById($imageid)
    {
        if (!isset($this->images[$imageid])) {
            $image = $this->em->getRepository('ApplicationSonataMediaBundle:Media')->find($imageid);
            $this->images[$imageid] = $image;
        }
        return $this->images[$imageid];
    }
    
    private function getImageUrlByFormat($imageid, $format)
    {
        if (!isset($this->imageurls[$imageid][$format])) {
            $image = $this->getImageById($imageid);
            if ($image) $this->imageurls[$imageid][$format] = $this->appmedia->getImageUrl($image, $format);
            else $this->imageurls[$imageid][$format] = false;
        }
        return $this->imageurls[$imageid][$format];
    }
}