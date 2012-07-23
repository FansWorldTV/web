<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Application\Sonata\UserBundle\Entity\Notification;

class UserController extends SiteController
{

    const LIMIT_SEARCH = 20;
    const LIMIT_NOTIFICATIONS = 5;
    const LIMIT_PHOTOS = 8;
    const LIMIT_LIST_IDOLS = 15;

    /**
     * @Route("/u/{username}", name="user_wall")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function wallAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $hasComments = $this->getRepository('Comment')->countBy(array('target' => $user->getId()));
        $hasComments = $hasComments > 0 ? true : false;
        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $friendGroups = $this->getRepository('FriendGroup')->findBy(array('author' => $loggedUser->getId()));

        return array('user' => $user, 'friendgroups' => $friendGroups, 'hasComments' => $hasComments, 'isHome' => true);
    }

    /**
     * @Route("/u/{username}/info", name="user_detail")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function detailAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $friendGroups = $this->getRepository('FriendGroup')->findBy(array('author' => $loggedUser->getId()));

        $categories = $this->getRepository('InterestCategory')->findBy(array(), array('title' => 'ASC'));

        $interests = array();
        foreach ($categories as $category) {
            $interestsRepo = $this->getRepository('Interest')->matching($category->getId(), null, $user->getId());

            $interests[$category->getId()]['category'] = $category->getTitle();
            foreach ($interestsRepo as $element) {
                $interests[$category->getId()]['interest'][] = array(
                    'title' => $element->getTitle(),
                    'image' => $this->getImageUrl($element->getImage())
                );
            }
        }

        return array('user' => $user, 'friendgroups' => $friendGroups, 'interests' => $interests);
    }

    /**
     * @Template
     */
    public function registerAction()
    {
        return array();
    }

    /**
     *  get params (all optional):
     *   - text (partial match)
     *   - page
     *  @Route("/ajax/matching/", name="user_ajaxmatching")
     */
    public function ajaxMatching()
    {
        $request = $this->getRequest();
        $text = $request->get('text');
        $page = $request->get('page');
        $limit = null;
        $offset = null;

        $user = $this->get('security.context')->getToken()->getUser();

        if (!($user instanceof User))
            throw new AccessDeniedException('Acceso denegado');

        if ($page !== null) {
            $page--;
            $limit = self::LIMIT_AJAX_GET;
            $offset = $limit * $page;
        }

        $friends = $this->getRepository('User')->matching($user, $text, $limit, $offset);

        $response = array();
        foreach ($friends as $friend) {
            $response[] = array(
                'id' => $friend->getId(),
                'value' => (string) $friend,
                'add' => $friend->getId(),
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/notification-number/", name="user_ajaxnotificationnumber") 
     */
    public function ajaxNotificationNumber()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $notiRepo = $this->getRepository('Notification');
        $number = $notiRepo->countLatest($user, false);

        return $this->jsonResponse(array('number' => $number));
    }

    /**
     *  @Route("/ajax/get-notifications/", name="user_ajaxnotifications")
     */
    public function ajaxNotifications()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $notiRepo = $this->getRepository('Notification');
        $notifications = $notiRepo->latest($user, null, self::LIMIT_NOTIFICATIONS);
        $countAll = $notiRepo->countBy(array('author' => $user->getId()));

        $response = array();
        foreach ($notifications as $notification) {
            $response['notifications'][] = array(
                'readed' => $notification->getReaded(),
                'view' => $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification))
            );
        }

        $response['countAll'] = $countAll;
        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/get-notification/", name="user_ajaxnotification")
     */
    public function ajaxNotification()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $notificationId = $request->get('id', false);
        $response = array();

        if ($notificationId) {
            $notiRepo = $this->getRepository('Notification');
            $notification = $notiRepo->find($notificationId);

            $response = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
        }

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/read-notification/", name="user_ajaxdeletenotification")
     */
    public function ajaxDeleteNotification()
    {
        $request = $this->getRequest();
        $notificationId = $request->get('id', false);
        $user = $this->get('security.context')->getToken()->getUser();

        $response = false;

        if ($notificationId) {
            try {
                $notification = $this->getRepository('Notification')->find($notificationId);

                if (!($user instanceof User))
                    throw new \Exception('Must be logged in');
                if ($notification->getTarget() != $user)
                    throw new \Exception('Wrong user');

                $notification->setReaded(true);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($notification);
                $em->flush();

                $response = true;
            } catch (\Exception $exc) {
                $response = $exc->getMessage();
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/number-friend-requests/", name="user_ajaxnumberofpendingrequests") 
     */
    public function ajaxNumberOfPendingRequests()
    {
        $friendRepo = $this->getRepository('Friendship');

        $response = false;

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $countTotal = $friendRepo->CountPending($user);

            $response = array('number' => $countTotal);
        }

        return $this->jsonResponse($response);
    }

    /**
     * method to get the friendship data! ;)
     * 
     *  @Route("/ajax/getfriendship/", name="user_ajaxgetfriendship") 
     */
    public function ajaxGetFriendship()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $friendRepo = $this->getRepository('Friendship');
        $response = false;
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);

        if ($user instanceof User) {
            if ($friendshipId) {
                $friendship = $friendRepo->find($friendshipId);
                if ($friendship) {
                    $response = array(
                        'friendship' => array(
                            'id' => $friendship->getId(),
                            'ts' => $friendship->getCreatedat()->format('U')
                        ),
                        'author' => array(
                            'id' => $friendship->getAuthor()->getId(),
                            'name' => (string) $friendship->getAuthor(),
                            'image' => $this->getImageUrl($friendship->getAuthor()->getImage()),
                            'url' => $this->generateUrl('user_wall', array('username' => $friendship->getAuthor()->getUsername()))
                        ),
                        'target' => array(
                            'id' => $friendship->getTarget()->getId(),
                            'restricted' => $friendship->getTarget()->getRestricted()
                        )
                    );
                }
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/pending-friends/", name = "user_ajaxpendingfriends")
     *  
     */
    public function ajaxPendingFriendsAction()
    {
        $request = $this->getRequest();
        $limit = $request->get('limit', self::LIMIT_SEARCH);
        $page = $request->get('page', 1);

        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $friendRepo = $this->getRepository('Friendship');

        $response = false;

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $response = array();
            $pending = $friendRepo->Pending($user, $limit, $offset);
            $countTotal = $friendRepo->CountPending($user);

            $response['total'] = $countTotal;

            if ($countTotal > ($page * $limit)) {
                $response['gotMore'] = true;
            } else {
                $response['gotMore'] = false;
            }

            foreach ($pending as $element) {
                $media = $element->getAuthor()->getImage();
                $response['friendships'][] = array(
                    'friendship' => array(
                        'id' => $element->getId(),
                        'ts' => $element->getCreatedat()->format('U')
                    ),
                    'user' => array(
                        'id' => $element->getAuthor()->getId(),
                        'name' => (string) $element->getAuthor(),
                        'image' => $this->getImageUrl($media),
                        'url' => $this->generateUrl('user_wall', array('username' => $element->getAuthor()->getUsername()))
                    )
                );
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/ajax/accept-request/", name = "user_ajaxacceptrequest")
     */
    public function ajaxAcceptRequestAction()
    {
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);
        $user = $this->get('security.context')->getToken()->getUser();

        $error = true;

        if ($friendshipId) {
            try {
                if (!($user instanceof User))
                    throw new \Exception('Must be logged in');
                $friendshipRepo = $this->getRepository('Friendship');
                $friendship = $friendshipRepo->findOneBy(array('id' => $friendshipId));

                if ($friendship->getActive())
                    throw new \Exception('Already accepted');
                if ($friendship->getTarget() != $user)
                    throw new \Exception('Wrong user');

                $friendship->setActive(true);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($friendship);
                $em->flush();

                $error = false;
            } catch (\Exception $exc) {
                $error = $exc->getMessage();
            }
        }

        $response = new Response(json_encode(array('error' => $error)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/ajax/deny-request/", name = "user_ajaxdenyrequest")
     */
    public function ajaxDenyRequestAction()
    {
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);
        $user = $this->get('security.context')->getToken()->getUser();



        $error = true;

        if ($friendshipId) {
            try {
                if (!($user instanceof User))
                    throw new \Exception('Must be logged in');
                $friendshipRepo = $this->getRepository('Friendship');
                $friendship = $friendshipRepo->find($friendshipId);

                if ($friendship->getTarget() != $user)
                    throw new \Exception('Wrong user');

                $em = $this->getDoctrine()->getEntityManager();
                $em->remove($friendship);
                $em->flush();

                $error = false;
            } catch (\Exception $exc) {
                $error = $exc->getMessage();
            }
        }

        $response = new Response(json_encode(array('error' => $error)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/user/requests", name="user_friendrequests")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function friendRequestsAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $friendsRequest = false;
        if ($user instanceof User) {
            $pending = $this->getRepository('Friendship')->Pending($user);
            foreach ($pending as $element) {
                $media = $element->getAuthor()->getImage();
                $friendsRequest[] = array(
                    'friendship' => array(
                        'id' => $element->getId(),
                        'ts' => $element->getCreatedat()->format('U')
                    ),
                    'user' => array(
                        'id' => $element->getAuthor()->getId(),
                        'name' => (string) $element->getAuthor(),
                        'image' => $this->getImageUrl($media),
                        'url' => $this->generateUrl('user_wall', array('username' => $element->getAuthor()->getUsername()))
                    )
                );
            }
        }

        return array('requests' => $friendsRequest);
    }

    /**
     * @Route("/user/notifications", name="user_notifications")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function notificationsAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $notiRepo = $this->getRepository('Notification');
        $notifications = $notiRepo->findBy(array('target' => $user->getId()), array('createdAt' => 'DESC'));
        $response = array();
        foreach ($notifications as $notification) {
            $response[] = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
        }
        return array('notifications' => $response, 'user' => $user);
    }

    /**
     * @Route("/u/{username}/photos", name="user_photos")
     * @Template
     */
    public function photosAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);

        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;

        $photos = $this->getRepository('Photo')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);

        $photosTotalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
        $albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));

        $viewMorePhotos = $photosTotalCount > self::LIMIT_PHOTOS ? true : false;
        $viewMoreAlbums = $albumsTotalCount > self::LIMIT_PHOTOS ? true : false;

        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $friendGroups = $this->getRepository('FriendGroup')->findBy(array('author' => $loggedUser->getId()));

        return array(
            'user' => $user,
            'isLoggedUser' => $isLoggedUser,
            'photos' => $photos,
            'albums' => $albums,
            'viewMorePhotos' => $viewMorePhotos,
            'viewMoreAlbums' => $viewMoreAlbums,
            'friendgroups' => $friendGroups
        );
    }

    /**
     * @Route("/u/{username}/photos/list", name="user_listphotos")
     * @Template
     */
    public function listPhotosAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;
        $photos = $this->getRepository('Photo')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $totalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));

        return array(
            'user' => $user,
            'photos' => $photos,
            'gotMore' => $totalCount > self::LIMIT_PHOTOS ? true : false
        );
    }

    /**
     * @Route("/u/{username}/albums", name="user_listalbums")
     * @Template
     */
    public function listAlbumsAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $totalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));

        return array(
            'user' => $user,
            'albums' => $albums,
            'gotMore' => $totalCount > self::LIMIT_PHOTOS ? true : false
        );
    }

    /**
     * @Route("/invite_users/", name = "user_invite")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function inviteAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $url = $this->get('contact.importer')->inviteUrl($user);
        return array(
            'url' => $url
        );
    }

    /**
     * @Route("/user/change_image", name="user_change_image")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function changeImageAction()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $media = $user->getImage();

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'file' => new \Symfony\Component\Validator\Constraints\Image()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('file', 'file', array('required' => true, 'label' => 'Archivo'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $mediaManager = $this->get("sonata.media.manager.media");

                    $media = new Media();
                    $media->setBinaryContent($data['file']);
                    $media->setContext('default');
                    $media->setProviderName('sonata.media.provider.image');

                    $mediaManager->save($media);

                    $user->setImage($media);
                    $em->persist($user);
                    $em->flush();
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto'));
            }
        }

        return array('media' => $media, 'form' => $form->createView());
    }

    /**
     * @Route("/u/{username}/idols", name="user_idols")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function idolTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $idolships = $this->getRepository('Idolship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'), self::LIMIT_LIST_IDOLS);
        $idolshipsCount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));

        $return = array(
            'idolships' => $idolships,
            'addMore' => $idolshipsCount > self::LIMIT_LIST_IDOLS ? true : false,
            'user' => $user
        );

        return $return;
    }

    /**
     * @Route("/ajax/user_idols/", name="user_ajaxidols")
     */
    public function ajaxGetUserIdols()
    {
        $request = $this->getRequest();
        $userId = $request->get('userid', false);
        $page = $request->get('page', 0);

        $response = array(
            'addMore' => false,
            'idolship' => array()
        );

        

        if (!$userId) {
            $user = $this->get('security.context')->getToken()->getUser();
        } else {
            $user = $this->getRepository('User')->find($userId);
        }

        $idolshipsCount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));
        
        if (( $idolshipsCount / self::LIMIT_LIST_IDOLS ) > $page) {
            $response['addMore'] = true;
        }
        
        if ($page > 0) {
            $page--;
            $offset = self::LIMIT_LIST_IDOLS * $page;
        } else {
            $offset = 0;
        }
        
        $idolships = $this->getRepository('Idolship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'), self::LIMIT_LIST_IDOLS, $offset);


        foreach ($idolships as $idolship) {
            $idol = $idolship->getIdol();
            $response['idolship'][] = array(
                'name' => (string) $idol,
                'avatar' => $this->getImageUrl($idol->getImage()),
                'id' => $idol->getId()
            );
        }
        
        return $this->jsonResponse($response);
    }
    
    /**
     * @Route("/u/{username}/photos/{id}/{slug}", name="user_photodetail")
     * @Template
     */
    public function photoDetailAction($username,$id)
    {
    	$user = $this->getRepository('User')->findOneByUsername($username);
    	
    	$photoRepo = $this->getRepository('Photo');
    	$photo = $photoRepo->findOneBy(array('id' => $id, 'active' => true));
    	
    	$next = $photoRepo->getNextActive($id);
    	$prev = $photoRepo->getPrevActive($id);
    	
    	if (!$user) {
    		throw new HttpException(404, "No existe el usuario");
    	}else
    		$this->get('visitator')->visit($photo);
    
    	$loggedUser = $this->get('security.context')->getToken()->getUser();
    	$isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;
    	 
    	$this->securityCheck($photo);
    
    
    	return array(
    			'user' => $user,
    			'isLoggedUser' => $isLoggedUser,
    			'photo' => $photo,
    			'prev' => $prev,
    			'next' => $next
    	);
    }
    
    /**
     * @Route("/u/{username}/badges", name="user_badges")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function badgeTabAction($username)
    {
    	$user = $this->getRepository('User')->findOneByUsername($username);
    	if (!$user) {
    		throw new HttpException(404, "No existe el usuario");
    	}else
    		$this->get('visitator')->visit($user);
    
    	$badges = $this->getRepository('BadgeStep')->byUser($user);
    	//$idolshipsCount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));
    
    	$return = array(
    			'badges' => $badges,
    			//'addMore' => $idolshipsCount > self::LIMIT_LIST_IDOLS ? true : false,
    			'user' => $user
    	);
    
    	return $return;
    }

}
