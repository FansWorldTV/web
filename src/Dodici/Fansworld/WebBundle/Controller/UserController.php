<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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

    /**
     * @Route("/search/", name = "user_search")
     * @Template
     */
    public function searchAction()
    {
        return array();
    }

    /**
     *  @Route("/ajax/search/", name = "user_ajaxsearch")
     *  
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');

        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $userRepo = $this->getRepository('User');

        $response = false;
        if ($query) {
            $response = array();
            $user = $this->get('security.context')->getToken()->getUser();

            if ($user instanceof User) {
                $search = $userRepo->SearchFront($user, $query, false, self::LIMIT_SEARCH, $offset);

                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $this->getImageUrl($element[0]->getImage());
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }


                $countSearch = $userRepo->CountSearchFront($user, $query);

                if ($countSearch > $offset) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/friends/", name="user_friends")
     * @Template
     */
    public function friendsAction()
    {
        $userRepo = $this->getRepository('User');
        $user = $this->get('security.context')->getToken()->getUser();

        $friends = $userRepo->FriendUsers($user, null, self::LIMIT_SEARCH, null);

        $canAddMore = false;
        if ($userRepo->CountFriendUsers($user) > self::LIMIT_SEARCH) {
            $canAddMore = true;
        }
        return array('friends' => $friends, 'canAddMore' => $canAddMore);
    }

    /**
     *  @Route("/ajax/friends/", name="user_ajaxfriends") 
     */
    public function ajaxFriendsAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');
        $userRepo = $this->getRepository('User');
        $user = $this->get('security.context')->getToken()->getUser();

        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $response = false;

        if ($query && $user instanceof User) {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            $countFriendUsers = $userRepo->CountFriendUsers($user, $query);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $this->getImageUrl($element[0]->getImage());
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }

                $search = $userRepo->SearchFront($user, $query, false, null, $offset);
                if ($countFriendUsers > $offset) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/user/{id}", name="user_detail", requirements={"id"="\d+"})
     * @Template
     */
    public function detailAction($id)
    {


        $user = $this->getRepository('User')->find($id);
        if (!$user) {
            echo "No existe el usuario";
            exit;
        }

        return array('user' => $user);
    }

    /**
     * @Template
     */
    public function registerAction()
    {
        return array();
    }

    /**
     *  @Route("/ajax/notification-number/", name="user_ajaxnotificationnumber") 
     */
    public function ajaxNotificationNumber()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $notiRepo = $this->getRepository('Notification');
        $number = $notiRepo->countLatest($user);

        return $this->jsonResponse(array('number' => $number));
    }

    /**
     *  @Route("/ajax/get-notifications/", name="user_ajaxnotifications")
     */
    public function ajaxNotifications()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $notiRepo = $this->getRepository('Notification');
        $notifications = $notiRepo->latest($user, false, self::LIMIT_NOTIFICATIONS);

        $response = array();
        foreach ($notifications as $notification) {
            $response[] = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
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

        $response = false;

        if ($notificationId) {
            try {
                $notification = $this->getRepository('Notification')->find($notificationId);

                $notification->setReaded(true);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($notification);
                $em->flush();

                $response = true;
            } catch (Exception $exc) {
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

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
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
            $mediaservice = $this->get('sonata.media.pool');

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
                        'url' => $this->generateUrl('user_detail', array('id' => $element->getAuthor()->getId()))
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

        $error = true;

        if ($friendshipId) {
            try {
                $friendshipRepo = $this->getRepository('Friendship');
                $friendship = $friendshipRepo->findOneBy(array('id' => $friendshipId));
                $friendship->setActive(true);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($friendship);
                $em->flush();

                $error = false;
            } catch (Exception $exc) {
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

        $error = true;

        if ($friendshipId) {
            try {
                $friendshipRepo = $this->getRepository('Friendship');
                $friendship = $friendshipRepo->find($friendshipId);

                $em = $this->getDoctrine()->getEntityManager();
                $em->remove($friendship);
                $em->flush();

                $error = false;
            } catch (Exception $exc) {
                $error = $exc->getMessage();
            }
        }

        $response = new Response(json_encode(array('error' => $error)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/user/requests", name="user_friendrequests")
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
                        'url' => $this->generateUrl('user_detail', array('id' => $element->getAuthor()->getId()))
                    )
                );
            }
        }

        return array('requests' => $friendsRequest);
    }

    /**
     * @Route("/user/{id}/photos", name="user_photos")
     * @Template
     */
    public function photosAction($id)
    {
        $user = $this->getRepository('User')->find($id);
        $loggedUser = $this->get('security.context')->getToken()->getUser();
        $isLoggedUser = $user->getId() == $loggedUser->getId() ? true : false;

        $photosRepo = $this->getRepository('Photo')->findBy(array('author' => $user->getId()), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);
        $albumsRepo = $this->getRepository('Album')->findBy(array('author' => $user->getId()), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS);

        $photosTotalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId()));
        $albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId()));

        $viewMorePhotos = $photosTotalCount > self::LIMIT_PHOTOS ? true : false;
        $viewMoreAlbums = $albumsTotalCount > self::LIMIT_PHOTOS ? true : false;

        $photos = array();
        foreach ($photosRepo as $photo) {
            $photos[] = array(
                'id' => $photo->getId(),
                'image' => $this->getImageUrl($photo->getImage())
            );
        }

        $albums = array();
        foreach ($albumsRepo as $album) {
            $image = $this->getRepository('Photo')->findOneBy(array('album'=>$album->getId()));
            $albums[] = array(
                'image' => $this->getImageUrl($image),
                'id' => $album->getId(),
                'title' => $album->getTitle(),
                'countImages' => count($album->getPhotos()),
                'comments' => count($album->getComments())
            );
        }

        return array(
            'user' => $user,
            'isLoggedUser' => $isLoggedUser,
            'photos' => $photos,
            'albums' => $albums,
            'viewMorePhotos' => $viewMorePhotos,
            'viewMoreAlbums' => $viewMoreAlbums
        );
    }

    /**
     * @Route("/invite_users/", name = "user_invite")
     * @Template
     */
    public function inviteAction()
    {
        return array();
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
                    'file' => new \Symfony\Component\Validator\Constraints\File()
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

}
