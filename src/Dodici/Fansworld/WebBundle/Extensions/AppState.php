<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Entity\Idol;

use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class AppState
{

    const LIMIT_WALL = 10;

    protected $security_context;
    protected $request;
    protected $em;
    protected $user;
    protected $repos;

    function __construct(SecurityContext $security_context, EntityManager $em)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        $this->repos = array();
    }

    public function getMobile()
    {
        return (strpos($this->request->getHost(), 'm.') === 0);
    }

    public function getCulture($locale)
    {
        switch ($locale) {
            case 'es':
                return 'es_LA';
                break;
            case 'en':
            default:
                return 'en_US';
                break;
        }
    }

    public function canLike($entity)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        $rep = $this->getRepository('DodiciFansworldWebBundle:Liking');
        $liking = $rep->byUserAndEntity($user, $entity);

        if (count($liking) >= 1)
            return false;

        return $this->canView($entity);
    }

    public function canDislike($entity)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        $rep = $this->getRepository('DodiciFansworldWebBundle:Liking');
        $liking = $rep->byUserAndEntity($user, $entity);

        if (count($liking) >= 1)
            return true;
        else
            return false;
    }

    public function canShare($entity)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if (method_exists($entity, 'getAuthor')) {
            if ($user == $entity->getAuthor())
                return false;
        }

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        if (method_exists($entity, 'getPrivacy')) {
            if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
                if (method_exists($entity, 'getAuthor') && $entity->getAuthor()) {
                    if ($user == $entity->getAuthor())
                        return false;
                    $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
                    if (!$frep->UsersAreFriends($user, $entity->getAuthor()))
                        return false;
                }
            }
        }

        return true;
    }

    public function canView($entity)
    {
        $user = $this->user;

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        if ($entity instanceof Photo) {
            $album = $entity->getAlbum();
            if ($album && !$album->getActive())
                return false;
        }

        if ($this->security_context->isGranted('ROLE_ADMIN'))
            return true;

        if (property_exists($entity, 'author')) {
            if (($this->user instanceof User) && ($user == $entity->getAuthor()))
                return true;
        }

        if (method_exists($entity, 'getPrivacy')) {
            if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
                if (!($this->user instanceof User))
                    return false;
                if (method_exists($entity, 'getAuthor') && $entity->getAuthor()) {
                    if ($user == $entity->getAuthor())
                        return true;
                    $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
                    $fr = $frep->findOneBy(array('author' => $user, 'target' => $entity->getAuthor(), 'active' => true));
                    if (!$fr)
                        return false;
                }
            }
        }

        return true;
    }
    
    public function canViewField(User $user, $fieldname)
    {
        $viewer = $this->user;
        $privacies = $user->getPrivacy();
        
        if (isset($privacies[$fieldname])) {
            $privacy = $privacies[$fieldname];
            
            if ($privacy == Privacy::EVERYONE) return true;
            
            if ($viewer && ($privacy == Privacy::FRIENDS_ONLY)) {
                if ($viewer == $user) return true;
                $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
                $fr = $frep->findOneBy(array('author' => $viewer, 'target' => $user, 'active' => true));
            }
            
            if ($privacy == Privacy::ONLY_ME) return ($viewer == $user);
        } else {
            return (!($user->getRestricted()));
        }
        
        return false;
    }

    public function canEdit($entity)
    {
        return $this->canDelete($entity);
    }

    public function canDelete($entity)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if ($this->security_context->isGranted('ROLE_ADMIN'))
            return true;

        if (method_exists($entity, 'getAuthor')) {
            if ($user != $entity->getAuthor())
                return false;
        } else {
            return false;
        }

        if ($entity instanceof Comment) {
            if ($entity->getComment()) {
                return true;
            } else {
                if ($entity->getTarget() == $user) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    public function canComment($entity)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        if ($entity instanceof User) {
            if ($user == $entity)
                return true;

            $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
            if (!$frep->UsersAreFriends($user, $entity))
                return false;
        } else {
            if ($entity instanceof Comment) {
                if ($entity->getComment() !== null)
                    return false;
            }
            return $this->canView($entity);
        }

        return true;
    }

    public function canFriend(User $target)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if ($user == $target)
            return false;
        $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
        if ($frep->UsersAreFriends($user, $target))
            return false;

        return true;
    }

    public function friendshipWith(User $target)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        if ($user == $target)
            return false;
        $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
        return $frep->BetweenUsers($user, $target);
    }

    public function idolshipWith(Idol $target)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        $frep = $this->getRepository('DodiciFansworldWebBundle:Idolship');
        return $frep->findOneBy(array('author' => $user->getId(), 'idol' => $target->getId()));
    }

    public function teamshipWith(Team $team)
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        $frep = $this->getRepository('DodiciFansworldWebBundle:Teamship');
        return $frep->findOneBy(array('author' => $user->getId(), 'team' => $team->getId()));
    }

    public function getType($entity)
    {
        $name = $this->em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }

    public function getComments($entity)
    {
        $comments = $this->getRepository('DodiciFansworldWebBundle:Comment')->wallEntity($entity, $this->user, null, self::LIMIT_WALL, 0);
        return $comments;
    }

    public function getPrivacies()
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        return Privacy::getOptions();
    }

    public function getCities($country = null)
    {
        return $this->getRepository('DodiciFansworldWebBundle:City')->formChoices($country);
    }

    public function currentCareer(User $user)
    {
        $rep = $this->getRepository('DodiciFansworldWebBundle:HasInterest');
        $return = $rep->findBy(array('career' => true, 'author' => $user->getId()), array('dateFrom' => 'DESC'), 1);
        if ($return)
            return $return[0];
        return null;
    }

    public function getRequests()
    {
        if (!($this->user instanceof User))
            return false;
        $user = $this->user;

        $friendshipRepo = $this->getRepository('DodiciFansworldWebBundle:Friendship');
        $requests = $friendshipRepo->findBy(array('target' => $user->getId()), array('createdAt' => 'DESC'), 5);

        return $requests;
    }

    private function getRepository($repname)
    {
        if (!isset($this->repos[$repname])) {
            $this->repos[$repname] = $this->em->getRepository($repname);
        }
        return $this->repos[$repname];
    }

}