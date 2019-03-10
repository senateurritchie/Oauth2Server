<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserApp
 *
 * @ORM\Table(name="application")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserAppRepository")
 */
class UserApp{
    /**
    * @var int
    *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * l'auteur de cette application
    *
    * @var AppBundle\Entity\User
    *
    * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", cascade={"remove"})
    * @ORM\JoinColumn(nullable=false, onDelete="cascade")
    */
    private $author;

    /**
    * le compte user de l'application
    *
    * @var AppBundle\Entity\User
    *
    * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", cascade={"remove"})
    * @ORM\JoinColumn(nullable=false, onDelete="cascade")
    */
    private $user;

    /**
    * @var string
    *
    * @ORM\Column(name="name", type="string", length=30, unique=true)
    */
    private $name;

    /**
    * @var string
    *
    * @ORM\Column(name="description", type="string", length=200, nullable=true)
    */
    private $description;

    /**
    * @var string
    *
    * @ORM\Column(name="logo", type="string", length=200, nullable=true)
    */
    private $logo;

    /**
    * @var string
    *
    * @ORM\Column(name="website", type="string", length=200, nullable=true)
    */
    private $website;

    /**
    * @var string
    *
    * @ORM\Column(name="notifyurl", type="string", length=200, nullable=true)
    */
    private $notifyurl;

    /**
    * @var string
    *
    * @ORM\Column(name="token", type="string", length=30, unique=true)
    */
    private $token;

    /**
    * @var string
    *
    * @ORM\Column(name="secret", type="string", length=15)
    */
    private $secret;
    /**
    * @var string
    *
    * @ORM\Column(name="redirect_uri", type="string", nullable=true)
    */
    private $redirectUri;

    /**
    * @var \DateTime
    *
    * @ORM\Column(name="date", type="datetime")
    */
    private $date;



    /**
    * Constructor
    */
    public function __construct(){
        $this->apiaccesstokens = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(){
        return $this->id;
    }


    /**
     * Set name
     *
     * @param string $name
     *
     * @return Application
     */
    public function setName($name){
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Application
     */
    public function setDescription($description){
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(){
        return $this->description;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return Application
     */
    public function setLogo($logo){
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo(){
        return $this->logo;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return Application
     */
    public function setWebsite($website){
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite(){
        return $this->website;
    }

    /**
     * Set notifyurl
     *
     * @param string $notifyurl
     *
     * @return Application
     */
    public function setNotifyurl($notifyurl){
        $this->notifyurl = $notifyurl;

        return $this;
    }

    /**
     * Get notifyurl
     *
     * @return string
     */
    public function getNotifyurl(){
        return $this->notifyurl;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Application
     */
    public function setToken($token){
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(){
        return $this->token;
    }

    /**
     * Set secret
     *
     * @param string $secret
     *
     * @return Application
     */
    public function setSecret($secret){
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret(){
        return $this->secret;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Application
     */
    public function setDate($date){
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate(){
        return $this->date;
    }
   

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Application
     */
    public function setUser(\AppBundle\Entity\User $user){
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser(){
        return $this->user;
    }

    /**
     * Set redirectUri
     *
     * @param string $redirectUri
     *
     * @return Application
     */
    public function setRedirectUri($redirectUri){
        $this->redirectUri = $redirectUri;
        return $this;
    }

    /**
     * Get redirectUri
     *
     * @return string
     */
    public function getRedirectUri(){
        return $this->redirectUri;
    }


    /**
     * Set author
     *
     * @param \AppBundle\Entity\User $author
     *
     * @return Application
     */
    public function setAuthor(\AppBundle\Entity\User $author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \AppBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
