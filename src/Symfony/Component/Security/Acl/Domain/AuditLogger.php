<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\AuditableEntryInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\AuditLoggerInterface;

abstract class AuditLogger implements AuditLoggerInterface
{
    public function logIfNeeded($granted, EntryInterface $ace)
    {
        if (!$ace instanceof AuditableEntryInterface) {
            return;
        }
        
        if ($granted && $ace->isAuditSuccess()) {
            $this->doLog($granted, $ace);
        }
        else if (!$granted && $ace->isAuditFailure()) {
            $this->doLog($granted, $ace);
        }
    }
    
    abstract protected function doLog($granted, EntryInterface $ace);
}