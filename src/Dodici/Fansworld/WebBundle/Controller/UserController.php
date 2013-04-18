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
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

class UserController extends SiteController
{

    const LIMIT_SEARCH = 20;
    const LIMIT_NOTIFICATIONS = 5;
    const LIMIT_PHOTOS = 8;
    const LIMIT_VIDEOS = 10;
    const LIMIT_LIST_IDOLS = 15;

    /**
     * @Route("/u/{username}", name="user_land")
     * @Route("/u/{username}/wall", name="user_wall")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function wallTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->getUser();
        $friendGroups = $this->getRepository('FriendGroup')->findBy(array('author' => $loggedUser->getId()));


        return array(
            'user' => $user,
            'friendgroups' => $friendGroups,
            'isHome' => true,
        );
    }

    /**
     * @Route("/u/{username}/teams", name="user_teams")
     * @Template
     */
    public function teamsTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        } else {
            $this->get('visitator')->visit($user);
        }

        $loggedUser = $this->getUser();
        $friendGroups = $this->getRepository('FriendGroup')->findBy(array('author' => $loggedUser->getId()));
        $userTeams = $this->getRepository('Teamship')->byUser($user);

        return array(
            'user' => $user,
            'friendgroups' => $friendGroups,
            'userTeams' => $userTeams
        );
    }

    /**
     * @Route("/u/{username}/info", name="user_detail")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function infoTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->getUser();
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

        $personalData = array(
            'firstname',
            'lastname',
            'address',
            'phone',
            'twitter',
            'sex',
            'country',
            'city',
            'birthday',
            'email',
            'score',
            'level',
        );

        return array(
            'user' => $user,
            'friendgroups' => $friendGroups,
            'interests' => $interests,
            'personalData' => $personalData,
            'categories' => $this->getRepository('InterestCategory')->findBy(array(), array('title' => 'ASC'))
        );
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
     *  @Route("/ajax/matching", name="user_ajaxmatching")
     */
    public function ajaxMatching()
    {
        $request = $this->getRequest();
        $text = $request->get('text');
        $page = $request->get('page');
        $limit = null;
        $offset = null;

        $user = $this->getUser();

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
     *  @Route("/ajax/notification-number", name="user_ajaxnotificationnumber")
     */
    public function ajaxNotificationNumber()
    {
        $user = $this->getUser();
        $notiRepo = $this->getRepository('Notification');
        $number = $notiRepo->countLatest($user, false);

        return $this->jsonResponse(array('number' => $number));
    }

    /**
     *  @Route("/ajax/get-notifications", name="user_ajaxnotifications")
     */
    public function ajaxNotifications()
    {
        $user = $this->getUser();
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
     *  @Route("/ajax/get-notification", name="user_ajaxnotification")
     */
    public function ajaxNotification()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
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
     *  @Route("/ajax/read-notification", name="user_ajaxdeletenotification")
     */
    public function ajaxDeleteNotification()
    {
        $request = $this->getRequest();
        $notificationId = $request->get('id', false);
        $user = $this->getUser();

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
     *  @Route("/ajax/number-friend-requests", name="user_ajaxnumberofpendingrequests")
     */
    public function ajaxNumberOfPendingRequests()
    {
        $friendRepo = $this->getRepository('Friendship');

        $response = false;

        $user = $this->getUser();
        if ($user instanceof User) {
            $countTotal = $friendRepo->countPending($user);

            $response = array('number' => $countTotal);
        }

        return $this->jsonResponse($response);
    }

    /**
     * method to get the friendship data! ;)
     *
     *  @Route("/ajax/getfriendship", name="user_ajaxgetfriendship")
     */
    public function ajaxGetFriendship()
    {
        $user = $this->getUser();
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
                            'url' => $this->generateUrl('user_land', array('username' => $friendship->getAuthor()->getUsername()))
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
     *  @Route("/ajax/pending-friends", name = "user_ajaxpendingfriends")
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

        $user = $this->getUser();
        if ($user instanceof User) {
            $response = array();
            $pending = $friendRepo->pending($user, $limit, $offset);
            $countTotal = $friendRepo->countPending($user);

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
                        'url' => $this->generateUrl('user_land', array('username' => $element->getAuthor()->getUsername()))
                    )
                );
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/ajax/accept-request", name = "user_ajaxacceptrequest")
     */
    public function ajaxAcceptRequestAction()
    {
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);
        $user = $this->getUser();

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
     * @Route("/ajax/deny-request", name = "user_ajaxdenyrequest")
     */
    public function ajaxDenyRequestAction()
    {
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);
        $user = $this->getUser();



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
        $user = $this->getUser();

        $friendsRequest = false;
        if ($user instanceof User) {
            $pending = $this->getRepository('Friendship')->pending($user);
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
                        'url' => $this->generateUrl('user_land', array('username' => $element->getAuthor()->getUsername()))
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
        $user = $this->getUser();
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
    public function photosTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);

        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;

        $photos = $this->getRepository('Photo')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);

        $photosTotalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
        $albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));

        $viewMorePhotos = $photosTotalCount > self::LIMIT_PHOTOS ? true : false;
        $viewMoreAlbums = $albumsTotalCount > self::LIMIT_PHOTOS ? true : false;

        $loggedUser = $this->getUser();
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

        $loggedUser = $this->getUser();
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
     * @Secure(roles="ROLE_USER")
     */
    public function listAlbumsAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $loggedUser = $this->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;

        $photos = $this->getRepository('Photo')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);

        $photosTotalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
        $albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));

        $viewMorePhotos = $photosTotalCount > self::LIMIT_PHOTOS ? true : false;
        $viewMoreAlbums = $albumsTotalCount > self::LIMIT_PHOTOS ? true : false;

        return array(
            'user' => $user,
            'albums' => $albums,
            'photos' => $photos,
            'viewMorePhotos' => $viewMorePhotos,
            'viewMoreAlbums' => $viewMoreAlbums
        );
    }

    /**
     * @Route("/u/{username}/albums/{id}", name= "user_showalbum", requirements = {"id" = "\d+"})
     * @Template()
     */
    public function showAlbumAction($id, $username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $album = $this->getRepository('Album')->findOneBy(array('id' => $id, 'active' => true));

        $this->securityCheck($album);

        return array(
            'album' => $album,
            'user' => $user
        );
    }

    /**
     * @Route("/invite_users", name = "user_invite")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function inviteAction()
    {
        $user = $this->getUser();
        $url = $this->get('contact.importer')->inviteUrl($user);
        return array(
            'url' => $url,
            'user' => $user
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
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $media = $user->getImage();
        return array('media' => $media, 'user' => $user);
    }

    /**
     * @Route("/user/change_imageSave", name="user_change_imageSave")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function changeImageSaveAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $tempFile = $request->get('tempFile');
        $originalFileName = $request->get('originalFile');
        $realWidth = $request->get('width');
        $realHeight = $request->get('height');
        $type = $request->get('type');

        $lastdot = strrpos($originalFileName, '.');
        $originalFile = substr($originalFileName, 0, $lastdot);
        $ext = substr($originalFileName, $lastdot);

        $finish = false;
        $form = $this->_createForm();

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {

                    //$mediaManager = $this->get("sonata.media.manager.media");
                    //$media = new Media();
                    //$media->setBinaryContent($data['file']);
                    //$media->setContext('default');
                    //$media->setProviderName('sonata.media.provider.image');
                    //$mediaManager->save($media);
                    //$user->setImage($media);
                    //$em->persist($user);
                    //$em->flush();

                    $mediaManager = $this->get("sonata.media.manager.media");

                    $cropOptions = array(
                        "cropX" => $data['x'],
                        "cropY" => $data['y'],
                        "cropW" => $data['w'],
                        "cropH" => $data['h'],
                        "tempFile" => $tempFile,
                        "originalFile" => $originalFileName,
                        "extension" => $ext
                    );
                    $media = $this->_GenerateMediaCrop($cropOptions);

                    if ('profile' == $type) {
                        $user->setImage($media);
                    } else {
                        $user->setSplash($media);
                    }

                    $em->persist($user);
                    $em->flush();

                    $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                    $finish = true;
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto de perfil'));
            }
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'tempFile' => $tempFile,
            'originalFile' => $originalFileName,
            'ext' => $ext,
            'finish' => $finish,
            'realWidth' => $realWidth,
            'realHeight' => $realHeight,
            'type' => $type
        );
    }

    /**
     * @Route("/u/{username}/idols", name="user_idols")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function idolsTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

        $idolships = array(
            'ulClass' => 'idols',
            'containerClass' => 'idol-container',
            'list' => $this->getRepository('Idolship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'), self::LIMIT_LIST_IDOLS),
        );
        $idolshipsCount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));

        $return = array(
            'idolships' => $idolships,
            'addMore' => $idolshipsCount > self::LIMIT_LIST_IDOLS ? true : false,
            'user' => $user
        );

        return $return;
    }

    /**
     * @Route("/ajax/user_idols", name="user_ajaxidols")
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
            $user = $this->getUser();
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
                'id' => $idol->getId(),
                'slug' => $idol->getSlug()
            );
        }

        return $this->jsonResponse($response);
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

    /**
     * @Route("/u/{username}/fans", name="user_fans")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function fansTabAction($username)
    {
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);


        $friends = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container',
            'list' => $this->getRepository('User')->FriendUsers($user, null, self::LIMIT_SEARCH, null, 'score')
        );

        $return = array(
            'friends' => $friends,
            //'addMore' => $idolshipsCount > self::LIMIT_LIST_IDOLS ? true : false,
            'user' => $user
        );

        return $return;
    }

    /**
     * User videos
     *
     * @Route("/u/{username}/videos", name="user_videos")
     * @Template()
     */
    public function videosTabAction($username)
    {
        $author = $this->getRepository('User')->findOneByUsername($username);

        if (!$author) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($author);

        $user = $this->getUser();

        $videoRepo = $this->getRepository('Video');

        $videos = $videoRepo->search(null, $user, self::LIMIT_VIDEOS, null, null, null, $author, null, null, null, null);
        //$videos = $videoRepo->search(null, null, self::LIMIT_VIDEOS, null, null, null, null, null, null);
        $countAll = $videoRepo->countSearch(null, $user, null, null, $author, null, null, $author);

        $addMore = $countAll > self::LIMIT_VIDEOS ? true : false;

        $sorts = array(
            'id' => 'toggle-video-types',
            'class' => 'list-videos',
            'list' => array(
                array(
                    'name' => 'Destacados',
                    'dataType' => 0,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos',
                    'dataType' => 1,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos del día',
                    'dataType' => 3,
                    'class' => '',
                ),
                array(
                    'name' => 'Populares',
                    'dataType' => 2,
                    'class' => 'active',
                )
            )
        );

        return array(
            'sorts' => $sorts,
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $author
        );
    }

    /**
     *  @Route("/ajax/notifications-typecounts", name="user_ajaxgetnotifications_typecounts")
     */
    public function ajaxGetNotificationsTypeCounts()
    {
        $user = $this->getUser();
        $notificationRepo = $this->getRepository('Notification');
        $response = $notificationRepo->typeCounts($user, false);
        //return $this->jsonResponse(array('number' => $number));
        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/notification-setfake", name="notification_setfake")
     */
    public function ajaxSetNotificationFake()
    {
        $request = $this->getRequest();
        $type = $request->get('type', false);
        $notification = $this->getRepository('Notification')->findOneBy(array('type' => $type));
        $this->get('meteor')->push($notification);
    }

    /**
     *  @Route("/ajax/notification-getlatest", name="notification_getlatest")
     */
    public function ajaxLatestNotification()
    {
        $response = false;
        $request = $this->getRequest();
        $parentName = $request->get('parentName', false);
        $notifications = $this->getRepository('Notification')->latest($this->getUser(), false);
        foreach ($notifications as $notif) {
            if ($notif->getTypeParent() === $parentName)
                $notificationsUnread[] = $notif->getId();
        }
        if (isset($notificationsUnread))
            $response = $notificationsUnread;
        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/getphotosfrom-album", name="getphotosfrom_album")
     */
    public function ajaxGetPhotosFromAlbum()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $id = $request->get('id');

        //$albums = array();
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        //$albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));


        //$album = $this->getRepository('Album')->findOneBy(array('author' => $user->getId(), 'id' => $id, 'active' => true));
        $album = $this->getRepository('Album')->findOneBy(array('id' => $id, 'active' => true));
        //$album = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'id' => $id, 'active' => true));
        //$albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true));

        //$this->securityCheck($album);

        $response = array();
        $photos = $album->getPhotos();
        /*
        foreach ($photos as $photo) {
            $response['images'][] = $this->get('serializer')->values($photo, 'big');
        }
        */

        //$response['albums'] = $this->get('serializer')->values($albums);
        //$response['album'] = $this->get('serializer')->values($album);
        $response['photos'] = $this->get('serializer')->values($photos, 'big');
        return $this->jsonResponse($response);
    }


    private function _createForm()
    {
        $defaultData = array();
        $collectionConstraint = new Collection(array(
                    'x' => array(),
                    'y' => array(),
                    'w' => array(),
                    'h' => array()
                ));
        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('x', 'hidden', array('required' => false, 'data' => 0))
                ->add('y', 'hidden', array('required' => false, 'data' => 0))
                ->add('w', 'hidden', array('required' => false, 'data' => 0))
                ->add('h', 'hidden', array('required' => false, 'data' => 0))
                ->getForm();
        return $form;
    }

    private function _GenerateMediaCrop(array $options)
    {
        $imagine = new Imagine();
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $options['tempFile'];
        $format = $this->get('appmedia')->getType($path);

        $imageStream = $imagine->open($path);

        if ($options['cropW'] && $options['cropH']) {
            $imageStream = $imageStream
                    ->crop(new Point($options['cropX'], $options['cropY']), new Box($options['cropW'], $options['cropH']));
        }

        $metaData = array('filename' => $options['originalFile']);
        return $this->get('appmedia')->createImageFromBinary($imageStream->get($format), $metaData);
    }

}