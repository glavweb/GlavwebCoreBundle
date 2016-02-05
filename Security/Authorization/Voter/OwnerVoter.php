<?php

namespace Glavweb\CoreBundle\Security\Authorization\Voter;

use Sonata\UserBundle\Model\HasOwnerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class OwnerVoter
 * @package Glavweb\CoreBundle\Security\Authorization\Voter
 */
class OwnerVoter implements VoterInterface
{
    /**
     * Name of owner role
     */
    const ROLE_NAME_OWNER = 'OWNER';

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return bool true if this Voter supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        return true;
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     *
     * @return bool true if this Voter can process the class
     */
    public function supportsClass($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        return $reflectionClass->implementsInterface('Sonata\UserBundle\Model\HasOwnerInterface');
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param object|null $object     The object to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return int either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass(get_class($object))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        /** @var UserInterface $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return VoterInterface::ACCESS_ABSTAIN;
        }
        $userRoles = $user->getRoles();

        foreach ($attributes as $attribute) {
            $attribute = $attribute . '_' . self::ROLE_NAME_OWNER;
            if (in_array($attribute, $userRoles)) {
                $owners = $this->getOwners($object);

                foreach ($owners as $owner) {
                    if ($owner && $owner->getId() == $user->getId()) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    /**
     * @param $object
     * @return array
     */
    public function getOwners($object)
    {
        /** @var HasOwnerInterface $class */
        $class = get_class($object);
        $ownerFields = $class::getOwnerFields();

        $owners = array();
        foreach ($ownerFields as $ownerField) {
            if ($ownerField == 'id') {
                $owner = $object;
            } else {
                $method = 'get' . ucfirst($ownerField);
                $owner = $object->$method();
            }

            if ($owner instanceof UserInterface) {
                $owners[] = $owner;
            }
        }

        return $owners;
    }
}