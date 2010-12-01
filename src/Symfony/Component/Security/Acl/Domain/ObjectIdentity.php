<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * Represents a resource identity
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ObjectIdentity implements ObjectIdentityInterface
{
    protected $identifier;
    protected $type;
    
    /**
     * Constructor
     */
    public function __construct($identifier, $type)
    {
        $this->identifier = $identifier;
        $this->type = $type;
    }
    
    public static function fromDomainObject($domainObject)
    {
        if (!is_object($domainObject)) {
            throw new InvalidDomainObjectException('$domainObject must be an object.');
        }
        
        if ($domainObject instanceof DomainObjectInterface) {
            return new self($domainObject->getObjectIdentifier(), get_class($domainObject));
        }
        else if (method_exists($domainObject, 'getId')) {
            return new self($domainObject->getId(), get_class($domainObject));
        }
        
        throw new InvalidDomainObjectException('$domainObject must either implement the DomainObjectInterface, or have a method named "getId".');
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function equals(ObjectIdentityInterface $identity)
    {
        return $this->identifier == $identity->getIdentifier() && $this->type == $identity->getType();
    }
    
    public function __toString()
    {
        return sprintf('ObjectIdentity(%s, %s)', $this->identifier, $this->type);
    }
}