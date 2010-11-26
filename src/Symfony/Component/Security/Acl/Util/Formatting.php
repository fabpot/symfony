<?php

namespace Symfony\Component\Security\Acl\Util;

use Symfony\Component\Security\Acl\Model\PermissionInterface;

class Formatting
{
    public static function demergePatterns($original, $remove)
    {
        if (strlen($original) !== strlen($remove)) {
            throw new \InvalidArgumentException('$original, and $remove must be of equal length.');
        }
        
        for ($i=0,$c=strlen($original); $i<$c; $i++) {
            if ($remove[$i] !== PermissionInterface::RESERVED_OFF) {
                $original[$i] = PermissionInterface::RESERVED_OFF;
            }
        }
        
        return $original;
    }
    
    public static function mergePatterns($original, $extra)
    {
        if (strlen($original) !== strlen($extra)) {
            throw new \InvalidArgumentException('$original, and $extra must be of equal length.');
        }
        
        for ($i=0,$c=strlen($original); $i<$c; $i++) {
            if ($original[$i] === PermissionInterface::RESERVED_OFF) {
                $original[$i] = $extra[$i];
            }
        }
        
        return $original;
    }
    
    public static function getTextualRepresentation($mask, $code = PermissionInterface::RESERVED_ON)
    {
        $string = decbin($mask);
        $pattern = PermissionInterface::ALL_OFF;
        $temp = substr($pattern, 0, strlen($pattern) - strlen($string)) . $string;
        
        return str_replace(array('0', '1'), array(PermissionInterface::RESERVED_OFF, $code), $temp);
    }
    
    private final function __construct() {}
}