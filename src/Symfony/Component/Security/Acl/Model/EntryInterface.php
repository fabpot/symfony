<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * This class represents an individual entry in the ACL list.
 * 
 * Instances MUST be immutable, as they are returned by the ACL and should not
 * allow client modification.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface EntryInterface
{
    function getAcl();
    function getId();
    function getMask();
    function getSecurityIdentity();
    function getStrategy();
    function isGranting();
}