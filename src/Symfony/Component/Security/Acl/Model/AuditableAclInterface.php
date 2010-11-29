<?php

namespace Symfony\Component\Security\Acl\Model;

/**
 * This interface adds auditing capabilities to the ACL.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AuditableAclInterface extends MutableAclInterface
{
    function updateClassAuditing($index, $auditSuccess, $auditFailure);
    function updateClassFieldAuditing($index, $field, $auditSuccess, $auditFailure);
    function updateObjectAuditing($index, $auditSuccess, $auditFailure);
    function updateObjectFieldAuditing($index, $field, $auditSuccess, $auditFailure);
}