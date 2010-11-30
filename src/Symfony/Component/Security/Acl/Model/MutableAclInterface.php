<?php

namespace Symfony\Component\Security\Acl\Model;

use Doctrine\Common\NotifyPropertyChanged;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This interface adds mutators for the AclInterface.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface MutableAclInterface extends AclInterface, NotifyPropertyChanged
{
    function deleteClassAce($index);
    function deleteClassFieldAce($index, $field);
    function deleteObjectAce($index);
    function deleteObjectFieldAce($index, $field);
    function getId();
    function insertClassAce($index, $mask, SecurityIdentityInterface $sid, $granting);
    function insertClassFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting);
    function insertObjectAce($index, $mask, SecurityIdentityInterface $sid, $granting);
    function insertObjectFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting);
    function setEntriesInheriting($boolean);
    function setParentAcl(AclInterface $acl);
    function updateClassAce($index, $mask);
    function updateClassFieldAce($index, $field, $mask);
    function updateObjectAce($index, $mask);
    function updateObjectFieldAce($index, $field, $mask);
}