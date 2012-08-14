<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1970
 */
class DDC1970Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1970BaseUser'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1970UserCompany'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1970UserPerson'),
            ));
        } catch (\Exception $e) {

        }
    }

    public function testIssue()
    {

        $user1 = new DDC1970UserPerson();
        $user1->setName('beberlei');

        $user2 = new DDC1970UserPerson();
        $user2->setName('guilherme');

        $user3 = new DDC1970UserPerson();
        $user3->setName('asm89');

        $user2->setSponsor($user1);
        $user3->setSponsor($user2);
        $user1->setSponsor($user3);

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);
        $this->_em->flush();

        $this->_em->clear();

        $user = $this->_em->find(__NAMESPACE__ . '\DDC1970UserPerson', $user1->getId());
        $refs = $user->getReferrals();

        $this->assertEquals('beberlei', $user->getName());
        $this->assertFalse($refs->isInitialized(), "The collection is not initialized.");
        $this->assertInstanceOf(__NAMESPACE__.'\DDC1970BaseUser', $user->getSponsor(), "The sponsor cannot be lazy loaded, since it can be either a UserCompany or a UserPerson.");
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"user_person" = "DDC1970UserPerson", "user_company" = "DDC1970UserCompany"})
 */
abstract class DDC1970BaseUser
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $name;

    /**
     * @OneToMany(targetEntity="DDC1970BaseUser", mappedBy="sponsor")
     */
    protected $referrals;

    /**
     * @ManyToOne(targetEntity="DDC1970BaseUser", inversedBy="referrals")
     * @JoinColumn(name="sponsor_id", referencedColumnName="id")
     */
    protected $sponsor;

    public function __construct()
    {
        $this->referrals = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSponsor()
    {
        return $this->sponsor;
    }

    public function setSponsor(DDC1970BaseUser $user)
    {
        $this->sponsor = $user;
        $user->referrals->add($this);
    }

    public function getReferrals()
    {
        return $this->referrals;
    }
}

/**
 * @Entity
 */
class DDC1970UserPerson extends DDC1970BaseUser
{
}

/**
 * @Entity
 */
class DDC1970UserCompany extends DDC1970BaseUser
{
}
