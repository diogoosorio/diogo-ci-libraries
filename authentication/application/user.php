<?php
namespace models;

/**
 * Doctrine mapping of the table user.
 * 
 * @author Diogo Osório (diogo.g.osorio@gmail.com)
 * @version 1.0
 * @package tnbi
 * @subpackage orm
 * 
 * @Entity
 * @Table(name="user")
 */
class User 
{
    
    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /** 
     * The username. This is an unique value.
     * 
     * @Column(type="string", length=20, unique=true, nullable=false) 
     */
    protected $username;
    
    /** 
     * The email, this is an unique value.
     * 
     * @Column(type="string", length=255, unique=true, nullable=true) 
     */
    protected $email;
    
    
    /** @Column(type="string", length=255, nullable=true) */
    protected $password;
    
    
    /** 
     * If the user associates he's account with Facebook, this field will hold
     * he's Facebook ID.
     * 
     * @Column(type="bigint", nullable=true, unique=true) 
     */
    protected $facebookId;
    
    /** 
     * Authentication session salt.
     * 
     * @see Authentication::testAuthentication()
     * @Column(type="string", length=255, unique=true, nullable=true) 
     */
    protected $sessionSalt;
    
    /**
     *  Authentication cookie salt.
     * 
     *  @see Authentication::testAuthentication()
     *  @Column(type="string", length=255, unique=true, nullable=true) 
     */
    protected $cookieSalt;
    
    /**
     * The user's account status. It can be interperted as follows:
     *  0 - Account not confirmed
     *  1 - Account confirmed and enabled
     *  2 - Suspended account
     * 
     * @Column(type="smallint", nullable=false) */
    protected $status = 0;
    
    /** 
     * Activation salt, to confirm the user's email.
     * 
     * @Column(type="string", nullable=true, unique=true) 
     */
    protected $activationSalt;
    
    /**
     *  The user privilege on the website. Can be interperted as follows:
     *   0 - Non logged in user
     *   1 - Logged in normal user
     *   2 - Logged in editor
     *   3 - Logged in administrator 
     * 
     *  @see MY_Controller::_testPermissions()
     *  @Column(type="smallint", nullable=false) */
    protected $privileges = 1;      // Account privileges - defaults to 1 (normal user)
    
    /** 
     * If the user has an avatar. If so, the avatar will be present on the
     * public/upload/avatar folder and the file will have the same name as
     * the user's ID.
     * 
     * @Column(type="boolean", nullable=false) 
     */
    protected $avatar = false;
    
    /**
     * Does this user want to receive the newsletter?
     * 
     * @Column(type="boolean", nullable=false)
     */
    protected $newsletter = false;
    
    
    /**
     * Mapping to an User Type.
     * 
     * @ManyToOne(targetEntity="UserType")
     * @JoinColumn(name="user_type_id", referencedColumnName="id", nullable=true)
     */
    protected $userType;
    
    /**
     *  Mapping to all the user's votes.
     * 
     *  @OneToMany(targetEntity="Vote", mappedBy="user", cascade={"persist"}) 
     */
    protected $votes;
    
    
    
    public function __construct(){
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection;
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getFacebookId() {
        return $this->facebookId;
    }

    public function setFacebookId($facebookId) {
        $this->facebookId = $facebookId;
    }

    public function getSessionSalt() {
        return $this->sessionSalt;
    }

    public function setSessionSalt($sessionSalt) {
        $this->sessionSalt = $sessionSalt;
    }

    public function getCookieSalt() {
        return $this->cookieSalt;
    }

    public function setCookieSalt($cookieSalt) {
        $this->cookieSalt = $cookieSalt;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getPrivileges() {
        return $this->privileges;
    }

    public function setPrivileges($privileges) {
        $this->privileges = $privileges;
    }

    public function getAvatar() {
        return $this->avatar;
    }

    public function setAvatar($avatar) {
        $this->avatar = $avatar;
    }

    public function getUserType() {
        return $this->userType;
    }

    public function setUserType($userType) {
        $this->userType = $userType;
    }

    public function getVotes() {
        return $this->votes;
    }

    public function setVotes($votes) {
        $this->votes = $votes;
    }
    
    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getActivationSalt() {
        return $this->activationSalt;
    }

    public function setActivationSalt($activationSalt) {
        $this->activationSalt = $activationSalt;
    }


    public function getNewsletter() {
        return $this->newsletter;
    }

    public function setNewsletter($newsletter) {
        $this->newsletter = $newsletter;
    }



    
}
