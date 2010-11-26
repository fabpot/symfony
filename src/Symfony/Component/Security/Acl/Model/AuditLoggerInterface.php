<?php

namespace Symfony\Component\Security\Acl\Model;

interface AuditLoggerInterface
{
    function logIfNeeded($granted, EntryInterface $ace);
}