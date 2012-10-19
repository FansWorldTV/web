<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Doctrine\ORM\EntityManager;

class Sharer
{

    protected $request;
    protected $em;
    protected $appstate;
    protected $security_context;
    protected $user;
    protected $messenger;

    function __construct(EntityManager $em, SecurityContext $security_context, $appstate, $messenger)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
        $this->messenger = $messenger;
        $this->security_context = $security_context;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function share($sharedthing, $targets = null, $content = null, User $author = null, $sharefansworld = true)
    {
        if (!$author)
            $author = $this->user;
        if (!$author)
            throw new AccessDeniedException('Access denied');
        if ($targets && !is_array($targets))
            $targets = array($targets);

        if ($sharefansworld) {
            if (!$targets)
                $targets = array($author);
            else
                $targets[] = $author;
        }

        foreach ($targets as $target) {
            if ($target) {
                $this->shareToWall($sharedthing, $content, $target, $author);
            }
        }
    }

    public function shareToWall($sharedthing, $content = null, $targetentity = null, User $author = null, $privacy = Privacy::EVERYONE)
    {
        if (!$author)
            $author = $this->user;
        if (!$author)
            throw new AccessDeniedException('Access denied');
        if (!$targetentity)
            $targetentity = $author;

        $comment = new Comment();
        $comment->setType(Comment::TYPE_SHARE);
        $comment->setAuthor($author);
        $comment->setContent($content);
        $comment->setPrivacy($privacy);

        if ($targetentity instanceof User) {
            $comment->setTarget($targetentity);
        } else {
            $type = $this->appstate->getType($targetentity);
            if (property_exists($comment, $type)) {
                $comment->{'set' . ucfirst($type)}($targetentity);
            } else {
                throw new \Exception('Target entity is not an attribute of Comment');
            }
        }

        $share = $this->createShare($sharedthing);
        $comment->setShare($share);

        $this->em->persist($comment);
        $this->em->flush();
    }

    /**
     * Create a share object for the entity
     * @param mixed $entity
     */
    private function createShare($entity)
    {
        $classname = $this->appstate->getType($entity);
        $share = new Share();
        $methodname = 'set' . $classname;
        $share->$methodname($entity);
        if (property_exists($entity, 'author')) {
            $share->setAuthor($entity->getAuthor());
        }

        return $share;
    }

}