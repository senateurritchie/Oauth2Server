<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppAccessScope
 *
 * @ORM\Table(name="app_access_scope")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AppAccessScopeRepository")
 */
class AppAccessScope
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var AppBundle\Entity\Application
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Application")
     * @ORM\JoinColumn(onDelete="cascade", nullable=false)
     */
    private $application;

    /**
     * @var AppBundle\Entity\ApiAccessScope
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ApiAccessScope")
     * @ORM\JoinColumn(onDelete="cascade", nullable=false)
     */
    private $scope;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return AppAccessScope
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set application
     *
     * @param \AppBundle\Entity\Application $application
     *
     * @return AppAccessScope
     */
    public function setApplication(\AppBundle\Entity\Application $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return \AppBundle\Entity\Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Set scope
     *
     * @param \AppBundle\Entity\ApiAccessScope $scope
     *
     * @return AppAccessScope
     */
    public function setScope(\AppBundle\Entity\ApiAccessScope $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return \AppBundle\Entity\ApiAccessScope
     */
    public function getScope()
    {
        return $this->scope;
    }
}
