<?php
namespace \Symfony\Component\Security\Authentication\RememberMe;

interface PersistentTokenInterface
{
	function getUsername();
	
	function getSeries();
	
	function getTokenValue();
	
	function getLastUsed();
}