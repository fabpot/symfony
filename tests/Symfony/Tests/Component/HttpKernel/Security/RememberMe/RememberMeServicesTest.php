<?php

namespace Symfony\Tests\Component\HttpKernel\Security\RememberMe;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberMeServicesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorRequiresUserProvider()
    {
        $this->getService(false);
    }
    
    public function testConstructorWithMinimalArguments()
    {
        $service = $this->getService($provider = $this->getProvider(), array());
        
        $this->assertSame($provider, $service->getUserProvider());
        $this->assertEquals(array(), $service->getOptions());
        $this->assertNull($service->getLogger());
    }
    
    public function testConstructorWithoutLogger()
    {
        $service = $this->getService($provider = $this->getProvider(), $options = array('test'));
        
        $this->assertSame($provider, $service->getUserProvider());
        $this->assertEquals($options, $service->getOptions());
        $this->assertNull($service->getLogger());
    }
    
    /**
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::__construct
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::getOptions
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::getUserProvider
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::getLogger
     */
    public function testConstructor()
    {
        $service = $this->getService(
            $provider = $this->getProvider(),
            $options = array('foo'),
            $logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface')
        );
        
        $this->assertSame($provider, $service->getUserProvider());
        $this->assertEquals($options, $service->getOptions());
        $this->assertSame($logger, $service->getLogger());
    }
    
    /**
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::getKey
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::setKey
     */
    public function testSetKey()
    {
        $service = $this->getService();
        
        $this->assertNull($service->getKey());
        $service->setKey('foo');
        $this->assertEquals('foo', $service->getKey());
    }
    
    /**
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::getTokenProvider
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices::setTokenProvider
     */
    public function testSetTokenProvider()
    {
        $service = $this->getService();
        
        $this->assertNull($service->getTokenProvider());
        
        $provider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $service->setTokenProvider($provider);
        $this->assertSame($provider, $service->getTokenProvider());
    }
    
    public function testAutoLoginReturnsNullWhenNoCookie()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        
        $this->assertNull($service->autoLogin(new Request()));
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedMessage processAutoLoginCookie() must return a TokenInterface implementation.
     */
    public function testAutoLoginThrowsExceptionWhenImplementationDoesNotReturnTokenInterface()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request;
        $request->cookies->set('foo', 'foo');
        
        $service
            ->expects($this->once())
            ->method('processAutoLoginCookie')
            ->will($this->returnValue(null))
        ;
        
        $service->autoLogin($request);
    }
    
    public function testAutoLogin()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request();
        $request->cookies->set('foo', 'foo');
        
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        
        $service
            ->expects($this->once())
            ->method('processAutoLoginCookie')
            ->will($this->returnValue($token))
        ;
        
        $returnedToken = $service->autoLogin($request);
        
        $this->assertSame($token, $returnedToken);
    }
    
    public function testLogout()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request();
        $response = new Response();
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        
        $this->assertFalse($response->headers->has('Set-Cookie'));
        
        $service->logout($request, $response, $token);
        
        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertStringStartsWith('foo=;', $response->headers->get('Set-Cookie'));
    }
    
    public function testLoginFail()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request();
        $response = new Response();
        
        $this->assertFalse($response->headers->has('Set-Cookie'));
        
        $service->loginFail($request, $response);
        
        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertStringStartsWith('foo=;', $response->headers->get('Set-Cookie'));
    }
    
    public function testLoginSuccessIsNotProcessedWhenRememberMeIsNotRequested()
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request;
        $response = new Response;
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
         
        $service
            ->expects($this->never())
            ->method('onLoginSuccess')
            ->will($this->returnValue(null))
        ;
        
        $this->assertFalse($request->request->has('foo'));
        
        $service->loginSuccess($request, $response, $token);
    }
    
    public function testLoginSuccessWhenRememberMeAlwaysIsTrue()
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => true));
        $request = new Request;
        $response = new Response;
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        
        $service
            ->expects($this->once())
            ->method('onLoginSuccess')
            ->will($this->returnValue(null))
        ;
        
        $service->loginSuccess($request, $response, $token);
    }
    
    /**
     * @dataProvider getPositiveRememberMeParameterValues
     */
    public function testLoginSuccessWhenRememberMeParameterIsPositive($value)
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request; 
        $request->request->set('foo', $value);
        $response = new Response;
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        
        $service
            ->expects($this->once())
            ->method('onLoginSuccess')
            ->will($this->returnValue(true))
        ;
        
        $service->loginSuccess($request, $response, $token);
    }
    
    public function getPositiveRememberMeParameterValues()
    {
        return array('true', '1', 'on', 'yes');
    }
    
    public function testLoginSuccessRenewsRememberMeCookie()
    {
        $service = $this->getService();
        
        $token = $this->getMock(
        	'Symfony\Component\Security\Authentication\Token\RememberMeToken',
            array(),
            array(),
            'NonFunctionalRememberMeTokenMockClass',
            false
        );
        
        $service
            ->expects($this->once())
            ->method('onLoginSuccess')
            ->will($this->returnValue(null))
        ;
        
        $service->loginSuccess(new Request(), new Response(), $token);
    }
    
    protected function getService($userProvider = null, $options = array(), $logger = null)
    {
        if (null === $userProvider) {
            $userProvider = $this->getProvider();
        }
        
        return $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServices', array(
            $userProvider, $options, $logger
        ));
    }
    
    protected function getProvider()
    {
        $provider = $this->getMock('Symfony\Component\Security\User\UserProviderInterface');
        
        return $provider;
    }
}