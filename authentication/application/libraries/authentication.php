<?php

/**
 * This class provides a basic authentication system for this web application.
 * 
 * On instantiation, it checks if the user is authentication, through a sequence
 * of methods:
 * 
 * 1. Checks if there's a valid session. If so, terminates.
 * 
 * 2. If not, checks if there's a valid cookie. If so, creates a new session\cookie
 * and terminates.
 * 
 * 3. If not and if Facebook is enabled, checks for a valid Facebook session. If finds
 * one, matches the user FB ID against our database and creates a new session\cookie
 * if the operation is successfull.
 * 
 * The class expects that the Doctrine library is loaded an ready and that the entities
 * namespace is \models.
 * 
 * Sample usage:
 *  Load Doctrine library, must be acessible via $this->doctrine
 *  $this->load->library('authentication');
 *  
 *  if($this->authentication->isLogged) {
 *      $user = $this->authentication->getUser();
 *      $user->getUsername();
 *      (...)
 *  }
 * 
 * @author  Diogo Osório (diogo.g.osorio@gmail.com)
 * @package tnbi
 * @subpackage library
 * @version 1.0 
 */
class Authentication
{
    const FACEBOOK_SDK_PATH = 'application/libraries/Facebook/';

    protected $isLogged = false;
    protected $user = null;
    
    protected $facebookAuth = false;
    protected $facebookAppId;
    protected $facebookAppSecret;
    protected $facebookSdkPath;
    
    protected $sessionName      = 'cc_dffdg';
    protected $cookieName       = 'xx_jhauw';
    protected $noFacebookCookie = 'sdad_asdg';
    
    protected $CI;
    protected $em;
    
    
    /**
     * Gets the configuration parameters of the website (Configuration table)
     * and verifies if Facebook is an authentication option.
     * 
     * If so tries to include the SDK automatically. If the library isn't there,
     * you can allways call Authentication::setFacebookSdk and 
     * Authentication::enableFacebookAuth to enable authentication mechanism.
     */
    public function __construct() 
    {
        // Get CI and Doctrine's entity manager instances
        $this->CI =& get_instance();
        $this->em = $this->CI->doctrine->em;
        
        // Get the configuration table
        $configuration = $this->em->find('\models\Configuration', 1);
        
        // Should the Facebook authentication be used ?
        if($configuration->getLoginFacebook()) {
            $fbAppId        = $configuration->getFbAppId();
            $fbAppSecret    = $configuration->getFbAppSecret();
            
            // Both the appId and appSecret must be defined to procede
            if(strlen($fbAppId) > 0 && strlen($fbAppSecret) > 0) {
                $this->facebookAppId = $fbAppId;
                $this->facebookAppSecret = $fbAppSecret;
                
                $sdk = self::FACEBOOK_SDK_PATH . 'facebook.php';
                
                // If the file can be included, enable Facebook authentication
                if(file_exists($sdk)) {
                    require_once $sdk;
                    $this->facebookSdkPath = self::FACEBOOK_SDK_PATH . 'facebook.php';
                    $this->enableFacebookAuth();
                }
            } else {
                log_message('info', 'facebookAppId or facebookAppSecret not defined on the configuration table.');
            }
        }
        
        // Test for the user's current authentication!
        $this->testAuthentication();
    }
    
    /**
     * Sets the Facebook SDK file path, if the file indeed exists.
     * 
     * @param String $path 
     */
    public function setFacebookSdk($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $file = is_file($path) ? $path : $path . '/facebook.php';
        
        if(file_exists($file)) {
            require_once($file);
            $this->facebookSdkPath = $file;
        }
    }
    
    /**
     * If the SDK is loaded, enables Facebook authentication.
     */
    public function enableFacebookAuth()
    {
        if(class_exists('Facebook')) {
            $this->facebookAuth = true;
        }
    }
    
    /**
     * Overrides the default session name
     * 
     * @param string $name
     */
    public function setSessionName($name)
    {
        $this->sessionName = $name;
    }
    
    /**
     * Overrides the default cookie name
     * 
     * @param string $name 
     */
    public function setCookieName($name)
    {
        $this->cookieName = $name;
    }
    
    /**
     * Gets the current session name
     * 
     * @return string
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }
    
    /**
     * Gets the current cookie name
     * 
     * @return string
     */
    public function getCookieName()
    {
        return $this->cookieName;
    }
    
    /**
     * Gets the current \models\User authenticated user
     * 
     * @return mixed
     * The ORM user object or null.
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * Sets the current user. This may be used to force session creation
     * for a particular user
     * 
     * @param \models\User
     */
    public function setUser(\models\User $user)
    {
        $this->user = $user;
    }
    
    /**
     * Gets the current user status (if he's logged or 
     * not).
     *  
     * @return boolean
     */
    public function isLogged()
    {
        return $this->isLogged;
    }
    
    /**
     * Sequencally tests if there's a valid authentication for the user.
     * 
     * First checks if there's a valid session. If there isn't, checks if there's
     * a valid cookie.
     * 
     * Finally if Facebook authentication is enabled, checks for a valid
     * Facebook session.
     */
    protected function testAuthentication()
    {
        // Load dependecies
        $this->CI->load->library('session');
        $this->CI->load->helper('cookie');
        
        // Haven't found the user yet
        $found = false;
        
        // Verify if there's a session set
        $authSession = $this->CI->session->userdata($this->sessionName);
        if($authSession) $found = $this->testSessionAuth($authSession);
        
        // Verify if there's a cookie set
        if(!$found) {
            $authCookie = get_cookie($this->cookieName);
            if($authCookie) $found = $this->testCookieAuth($authCookie);
        }
        
        // If none and the Facebook Authentication is on, let's check that
        if(!$found && $this->facebookAuth) {
            $fbCookie = get_cookie($this->noFacebookCookie);
            if(!$fbCookie) $this->testFacebookAuth();
        }
    }
    
    
    /**
     * Tests if there's a valid session authentication.
     * 
     * @param string $string
     * The session string to be tested
     * 
     * @return boolean
     */
    protected function testSessionAuth($string)
    {
        // Get the user by the session salt
        $user = $this->em->getRepository('\models\User')->findOneBy(array('sessionSalt' => $string));
        
        // Store the user as this classe's property
        if(isset($user)) {
            $this->isLogged = true;
            $this->user = $user;
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Tests if the current authentication cookie on the client side is valid and
     * if so, creates a new session for the user.
     * 
     * @param string $string
     * The current cookie salt.
     * 
     * @return boolean
     */
    protected function testCookieAuth($string)
    {
        // Get the user by the cookie salt
        $user = $this->em->getRepository('\models\User')->findOneBy(array('cookieSalt' => $string));
        
        // Create new session
        if(isset($user)) {
            $this->createSession($user);
            return true;
        } else {
            return false;
        }
        
    }
    
    
    /**
     * Tests for a current valid Facebook session. If one is found, with
     * basic permissions, tries to find a match on our database.
     * 
     * If found, creates a new session for the user.
     * 
     * @return type 
     */
    protected function testFacebookAuth()
    {
        $facebook = new Facebook(array(
            'appId'     => $this->facebookAppId,
            'secret'    => $this->facebookAppSecret,
            'cookie'    => true
        ));
        
        // Find a valid user session @ Facebook
        $session = $facebook->getUser();
        
        if($session) {
            try {
                // Get my personal information
                $me = $facebook->api('/me');
                $id = $me['id'];
                
                // Query the database for a matching Facebook ID
                $user = $this->em->getRepository('\models\User')->findOneBy(array('facebookId' => $id));
                
                if(isset($user)) {
                    $this->isLogged = true;
                    $this->createSession($user);
                    return true;
                }
            } catch(FacebookApiException $exception) {
                log_message('debug', 'A Facebook API Exception was thrown: ' . $exception->getMessage());
            }
            
            set_cookie(array(
                'name'      => $this->noFacebookCookie,
                'value'     => 1,
                'expire'    => 60 * 30
            ));
            
            return false;
        }
    }
    
    
    /**
     * Creates new session && cookie salt. Stores them on the database and
     * on the client side.
     * 
     * @param \models\User $user 
     */
    public function createSession(\models\User $user)
    {
        // Load dependencies
        $this->CI->load->helper('cookie');
        $this->CI->load->helper('string');
        
        // Generate new session and cookie value
        $newSessionSalt = random_string('unique');
        $newCookieSalt  = random_string('unique');
        
        // Map it to the user
        $user->setSessionSalt($newSessionSalt);
        $user->setCookieSalt($newCookieSalt);
        
        // Create new session and cookie
        $this->CI->session->set_userdata(array($this->sessionName => $newSessionSalt));
        set_cookie(array(
            'name'      => $this->cookieName,
            'value'     => $newCookieSalt,
            'expire'    => 60 * 60 * 24 * 30
        ));
        
        // Test if the user has a valid Facebook session and if so, associate them
        if(!$user->getFacebookId()){
             $facebook = new Facebook(array(
                'appId'     => $this->facebookAppId,
                'secret'    => $this->facebookAppSecret,
                'cookie'    => true
            ));

            $session = $facebook->getUser();

            if($session) {
                try {
                    // Get my personal information
                    $me = $facebook->api('/me');
                    $id = $me['id'];
                    
                    // Associate it with FB account.
                    $user->setFacebookId($id);
                } catch(FacebookApiException $e) {
                    log_message('debug', 'A Facebook API Exception was thrown: ' . $exception->getMessage());
                }
            }
        }
        
        // Persist the changes
        $this->em->flush();
        
        // Update the user status...
        $this->testAuthentication();
    }
    
    
    /**
     * Destroys current session and authentication cookies.
     */
    public function destroySession()
    {
        $this->CI->load->helper('cookie');
        $this->CI->session->unset_userdata($this->sessionName);
        delete_cookie($this->cookieName);
    }
    
    
    /**
     * Get the Facebook login URL.
     * 
     * @param array $params
     * Params to be passed to the facebook::getLoginUrl.
     * 
     * The parameters:
     *  - redirect_url
     *  - scope
     * 
     * @link http://developers.facebook.com/docs/reference/api/permissions/
     * @return mixed
     * The login URL if Facebook authentication is enabled
     * or false if not.
     */
    public function getFacebookLoginUrl($params = null)
    {
        if($this->facebookAuth) {
            $facebook = new Facebook(array(
                'appId'     => $this->facebookAppId,
                'secret'    => $this->facebookAppSecret,
                'cookie'    => true
            ));
            
            if(!isset($params)) {
                $params['redirect_url'] = base_url();
            }
            
            return $facebook->getLoginUrl($params);
        }
        
        return false;
    }
    
    /**
     * Get the facebook logout URL and destroy current session.
     * 
     * @param array $params
     * An array with the Facebook::getLogoutUrl parameters:
     *  - next
     * 
     * @return mixed
     * The logout URL or false
     */
    public function getFacebookLogoutUrl($params = null)
    {
        if($this->facebookAuth) {
            $facebook = new Facebook(array(
                'appId'     => $this->facebookAppId,
                'secret'    => $this->facebookAppSecret,
                'cookie'    => true
            ));
            
            if(!isset($params)) $params['next'] = base_url();
            $facebook->destroySession();
            return $facebook->getLogoutUrl($params);
        }
        
        return false;
    }
    
    
    /**
     * Removes the no Facebook Cookie. While this cookie is
     * set, the app won't check Facebook for authentication.
     */
    public function deleteFacebookCookie()
    {
        $this->CI->load->helper('cookie');
        delete_cookie($this->noFacebookCookie);
    }
}