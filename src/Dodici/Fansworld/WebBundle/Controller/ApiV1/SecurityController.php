<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\ApiV1\BaseController;

/**
 * API controller - Security
 * V1
 * @Route("/api_v1")
 */
class SecurityController extends BaseController
{
	/**
     * [signed] Register
     * 
     * @Route("/register", name="api_v1_register")
     * @Method({"POST"})
     * 
     * Post params:
     * - email: string
     * - password: string
     * - firstname: string
     * - lastname: string
     * - <optional> idol[] : array (int, ...)
     * - <optional> team[] : array (int, ...)
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return
     *
     * on success:
     * @see SecurityController::loginAction()
     */
    public function registerAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                
                $email = null; $password = null; $firstname = null; $lastname = null;
                $rfields = array('email', 'password', 'firstname', 'lastname');
                foreach ($rfields as $rf) {
                    ${$rf} = $request->get($rf);
                    if (!${$rf}) throw new HttpException(400, 'Required parameters: ' . join(', ', $rfields));
                }
                
                $usermanager = $this->get('app_user.user_manager');
                $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
                
                $user = $usermanager->createUser();
                $user->setFirstname($firstname);
                $user->setLastname($lastname);
                $user->setEmail($email);
                $user->setPlainPassword($password);
                
                $existsmail = $usermanager->findUserByEmail($user->getEmail());
                if ($existsmail) throw new HttpException(400, 'User with email '.$user->getEmail().' already exists');
                
                $username = $user->getFirstname().'.'.$user->getLastname();
                $username = str_replace(' ', '.', $username);
                $exists = $usermanager->findUserByUsername($username);
                if ($exists) $username .= '.' . uniqid();
                $user->setUsername($username);
                
                if ($confirmationEnabled) {
                    $user->setEnabled(true);
                    $user->setExpiresAt(new \DateTime('+2 weeks'));
                } else {
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);
                }
                
                $validator = $this->get('validator');
                $errors = $validator->validate($user, array('Registration'));
            
                if (count($errors) > 0) {
                    $errorstr = (string)$errors;
                    $errorstr = str_replace('Application\Sonata\UserBundle\Entity\User.', '', $errorstr);
                    throw new HttpException(400, $user->getUsername() . ' ' .$errorstr);
                }
        
                $usermanager->updateUser($user);
                
                if ($confirmationEnabled) {
                    $this->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
                }
                
                if ($user->getId()) {
                    $token = $this->generateUserApiToken($user, $this->getApiKey());
                    
                    $return = array(
                        'token' => $token,
                        'user' => $this->userArray($user)
                    );
                    
                    if ($user->getExpiresAt()) $return['expires'] = $user->getExpiresAt()->format('U');
                    
                    $this->addIdolsTeams($user);
                    
                    return $this->result($return);
                    
                } else {
                    throw new HttpException(500, 'Something went wrong');
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
        
    /**
     * [signed] Login
     * 
     * @Route("/login", name="api_v1_login")
     * @Method({"POST"})
     *
     * Post params:
     * - username/email: string
     * - password: string, plain text
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return 
     * array (
     * 		token: hash used in other requests - string,
     * 		<if not confirmed> expires: int (ts UTC),
     * 		user: array (
     * 			id: int,
     * 			username: string,
     * 			email: string,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: array(id: int, url: string)
     * 		)
     * )
     */
    public function loginAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $username = $request->get('username');
                $email = $request->get('email');
                $password = $request->get('password');
                
                if (!$username && !$email) throw new HttpException(400, 'Requires username or email');
                if (!$password) throw new HttpException(400, 'Requires password');
                
                $user = $this->authUser($username ?: $email, $password);
                
                if ($user) {
                    if ($user->isExpired()) throw new HttpException('602-401', 'User account has expired');
                    
                    $token = $this->generateUserApiToken($user, $this->getApiKey());
                    
                    $return = array(
                        'token' => $token,
                        'user' => $this->userArray($user)
                    );
                    
                    if ($user->getExpiresAt()) $return['expires'] = $user->getExpiresAt()->format('U');
                    
                    return $this->result($return);
                    
                } else {
                    // Bad username/mail
                    throw new HttpException(401, 'Invalid username or password');
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Facebook Connect
     * 
     * @Route("/connect/facebook", name="api_v1_connect_facebook")
     * @Method({"POST"})
     * 
     * - If user already exists and is linked to FB: regular login
     * - If user with same email already exists, but is not linked to FB, links account to FB
     * - If user does not exist, registers the account
     * 
     * Get params:
     * - facebook_id: int
     * - access_token: string (facebook's access token for verification purposes)
     * - <optional> idol[] : array (int, ...)
     * - <optional> team[] : array (int, ...)
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return
     * @see SecurityController::loginAction()
     */
    public function facebookLoginAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $fbid = $request->get('facebook_id');
                $accesstoken = $request->get('access_token');
                
                $fbdata = $this->authFacebook($fbid, $accesstoken);
                
                $fbmanager = $this->get('my.facebook.user');
                $user = $fbmanager->loadUserByUsername($fbid, $accesstoken);
                
                if (!$user) throw new HttpException(401, 'Could not authenticate');
                
                $token = $this->generateUserApiToken($user, $this->getApiKey());
                
                $this->addIdolsTeams($user);
                
                $return = array(
                    'token' => $token,
                    'user' => $this->userArray($user)
                );
                
                return $this->result($return);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch(\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Reset password
     * 
     * @Route("/password/reset", name="api_v1_password_reset")
     * @Method({"POST"})
     * 
     * Sends an email with a reset password link to the user
     * 
     * Get params:
     * - username/email: string
     * - [signature params]
     * 
     * @return
     * array(
     * 		sent => true
     * )
     */
    public function resetPasswordAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $username = $request->get('username');
                $email = $request->get('email');
                
                if (!$username && !$email) throw new HttpException(400, 'Requires username or email');
                
                $usermanager = $this->get('app_user.user_manager');
                $user = $usermanager->findUserByUsernameOrEmail($username ?: $email);
                
                if (!$user) throw new HttpException(404, 'User not found');
                
                if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                    throw new HttpException(403, 'User has already requested a reset');
                }
                
                $user->generateConfirmationToken();
                $this->get('fos_user.mailer')->sendResettingEmailMessage($user);
                $user->setPasswordRequestedAt(new \DateTime());
                $usermanager->updateUser($user);
                
                $return = array(
                    'sent' => true
                );
                
                return $this->result($return);
                
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch(\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Change password
     * 
     * @Route("/password/change", name="api_v1_password_change")
     * @Method({"POST"})
     * 
     * Changes a user's password
     * 
     * Get params:
     * - username/email: string
     * - password: string, plain text
     * - new_password: string, plain text
     * - [signature params]
     * 
     * @return
     * @see SecurityController::loginAction()
     */
    public function changePasswordAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $username = $request->get('username');
                $email = $request->get('email');
                $password = $request->get('password');
                $newpassword = $request->get('new_password');
                
                if (!$username && !$email) throw new HttpException(400, 'Requires username or email');
                if (!$password) throw new HttpException(400, 'Requires password');
                if (!$newpassword) throw new HttpException(400, 'Requires new_password');
                
                $user = $this->authUser($username ?: $email, $password);
                
                if ($user) {
                    $user->setPlainPassword($newpassword);
                    $usermanager = $this->get('app_user.user_manager');
                    $usermanager->updateUser($user);
                    
                    $token = $this->generateUserApiToken($user, $this->getApiKey());
                    
                    $return = array(
                        'token' => $token,
                        'user' => $this->userArray($user)
                    );
                    
                    return $this->result($return);
                    
                } else {
                    // Bad username/mail
                    throw new HttpException(401, 'Invalid username or password');
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch(\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] validate user token
     * @Route("/token/validate", name="api_v1_token_validate")
     * 
     * Get params:
     * - user_id: int
     * - user_token: string
     * - [signature params]
     * 
     * @return
     * array (
     * 		valid: boolean,
     * 		<if valid = true> user: @see SecurityController::loginAction(),
     * 		<if not confirmed> expires: int (ts UTC)
     * )
     */
    public function tokenValidateAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $usertoken = $request->get('user_token');
                
                if (!$userid || !$usertoken) throw new HttpException(400, 'Requires user_id and user_token');
                
                $user = $this->getRepository('User')->find($userid);
                if (!$user) throw new HttpException(404, 'User not found');
                
                $realtoken = $this->generateUserApiToken($user, $this->getApiKey());
                
                if ($usertoken === $realtoken) {
                    if ($user->isExpired()) throw new HttpException('602-401', 'User account has expired');
                    
                    $return = array(
                        'valid' => true,
                        'user' => $this->userArray($user)
                    );
                } else {
                    $return = array('valid' => false);
                }
                
                if ($user->getExpiresAt()) $return['expires'] = $user->getExpiresAt()->format('U');
                
                return $this->result($return);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
