<?php

namespace Symfony\Component\Security\Acl\Model;

interface FieldEntryInterface extends EntryInterface
{
    function getField();
}