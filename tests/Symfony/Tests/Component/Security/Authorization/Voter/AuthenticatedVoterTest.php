<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Security\Authorization\Voter;

use Symfony\Component\Security\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Role\Role;

class AuthenticatedVoterTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsClass()
    {
        $voter = new AuthenticatedVoter($this->getResolver());
        $this->assertTrue($voter->supportsClass('stdClass'));
    }

    /**
     * @dataProvider getVoteTests
     */
    public function testVote($authenticated, $attributes, $expected)
    {
        $voter = new AuthenticatedVoter($this->getResolver());

        $this->assertSame($expected, $voter->vote($this->getToken($authenticated), null, $attributes));
    }

    public function getVoteTests()
    {
        return array(
            array('fully', array(), VoterInterface::ACCESS_ABSTAIN),
            array('fully', array('FOO'), VoterInterface::ACCESS_ABSTAIN),
            array('remembered', array(), VoterInterface::ACCESS_ABSTAIN),
            array('remembered', array('FOO'), VoterInterface::ACCESS_ABSTAIN),
            array('anonymously', array(), VoterInterface::ACCESS_ABSTAIN),
            array('anonymously', array('FOO'), VoterInterface::ACCESS_ABSTAIN),

            array('fully', array('IS_AUTHENTICATED_ANONYMOUSLY'), VoterInterface::ACCESS_GRANTED),
            array('remembered', array('IS_AUTHENTICATED_ANONYMOUSLY'), VoterInterface::ACCESS_GRANTED),
            array('anonymously', array('IS_AUTHENTICATED_ANONYMOUSLY'), VoterInterface::ACCESS_GRANTED),

            array('fully', array('IS_AUTHENTICATED_REMEMBERED'), VoterInterface::ACCESS_GRANTED),
            array('remembered', array('IS_AUTHENTICATED_REMEMBERED'), VoterInterface::ACCESS_GRANTED),
            array('anonymously', array('IS_AUTHENTICATED_REMEMBERED'), VoterInterface::ACCESS_DENIED),
            
            array('fully', array('IS_AUTHENTICATED_FULLY'), VoterInterface::ACCESS_GRANTED),
            array('remembered', array('IS_AUTHENTICATED_FULLY'), VoterInterface::ACCESS_DENIED),
            array('anonymously', array('IS_AUTHENTICATED_FULLY'), VoterInterface::ACCESS_DENIED),
        );
    }

    protected function getResolver()
    {
        return new AuthenticationTrustResolver(
        	'Symfony\\Component\\Security\\Authentication\\Token\\AnonymousToken',
            'Symfony\\Component\\Security\\Authentication\\Token\\RememberMeToken'
        );
    }
    
    protected function getToken($authenticated)
    {
        if ('fully' === $authenticated) {
            return $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        } 
        else if ('remembered' === $authenticated) {
            return $this->getMock('Symfony\Component\Security\Authentication\Token\RememberMeToken', array(), array(), '', false);
        }
        else {
            return $this->getMock('Symfony\Component\Security\Authentication\Token\AnonymousToken', null, array('', ''));
        }
    }
}
