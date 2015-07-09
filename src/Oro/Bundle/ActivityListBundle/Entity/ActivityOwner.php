<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

/**
 * @ORM\Table(
 *      name="oro_activity_owner",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="UNQ_activity_owner",
 *              columns={"activity_id", "user_id"}
 *          )
 *      }
 * )
 * @ORM\Entity()
 */
class ActivityOwner
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @var ActivityList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ActivityListBundle\Entity\ActivityList", inversedBy="activityOwners")
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $activity;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    protected $organization;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get activity
     *
     * @return ActivityList
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set activity
     *
     * @param ActivityList $activity
     *
     * @return $this
     */
    public function setActivity(ActivityList $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
