<?php

namespace Symfony\Component\Security\Acl\Model;

interface AuditableEntryInterface
{
    function isAuditFailure();
    function isAuditSuccess();
}