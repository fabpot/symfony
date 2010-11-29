<?php

namespace Symfony\Component\Security\Acl\Model;

interface AuditableEntryInterface extends EntryInterface
{
    function isAuditFailure();
    function isAuditSuccess();
}