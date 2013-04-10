<?php

namespace Oro\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\UserBundle\Entity\Acl;

use JMS\Serializer\Annotation\Type;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

/**
 * Role Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_access_role")
 */
class Role implements RoleInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     * @Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=30, nullable=false)
     * @Soap\ComplexType("string")
     * @Type("string")
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30)
     * @Soap\ComplexType("string")
     * @Type("string")
     */
    protected $label;

    /**
     * @ORM\ManyToMany(targetEntity="Acl", mappedBy="accessRoles")
     */
    protected $aclResources;

    /**
     * @var User[]
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles")
     */
    protected $users;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct($role = '')
    {
        $this->role  =
        $this->label = $role;
        $this->aclResources = new ArrayCollection();
    }

    /**
     * Return the role id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Return the role label field
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set role name only for newly created role
     *
     * @param  string            $role Role name
     * @return Role
     * @throws \RuntimeException
     */
    public function setRole($role)
    {
        $this->role = (string) strtoupper($role);

        // every role should be prefixed with 'ROLE_'
        if (strpos($this->role, 'ROLE_') !== 0) {
            $this->role = 'ROLE_' . $this->role;
        }

        return $this;
    }

    /**
     * Set the new label for role
     *
     * @param  string $label New label
     * @return Role
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;

        return $this;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->role;
    }

    /**
     * Add aclResources
     *
     * @param  \Oro\Bundle\UserBundle\Entity\Acl $aclResources
     * @return Role
     */
    public function addAclResource(Acl $aclResources)
    {
        $this->aclResources[] = $aclResources;

        return $this;
    }

    /**
     * Remove aclResources
     *
     * @param \Oro\Bundle\UserBundle\Entity\Acl $aclResources
     */
    public function removeAclResource(Acl $aclResources)
    {
        $this->aclResources->removeElement($aclResources);
    }

    /**
     * Get aclResources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAclResources()
    {
        return $this->aclResources;
    }

    public function setAclResources($resources)
    {
        $this->aclResources = $resources;
    }

    /**
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }
}
