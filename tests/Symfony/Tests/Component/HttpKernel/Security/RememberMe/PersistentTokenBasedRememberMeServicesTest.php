<?php

namespace Symfony\Tests\Component\HttpKernel\Security\RememberMe;

use Symfony\Component\Security\Authentication\Token\RememberMeToken;

use Symfony\Component\HttpFoundation\HeaderBag;

use Symfony\Component\Security\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Authentication\RememberMe\PersistentToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices;
use Symfony\Component\Security\Exception\TokenNotFoundException;
use Symfony\Component\Security\Exception\CookieTheftException;

class PersistentTokenBasedRememberMeServicesTest extends \PHPUnit_Framework_TestCase
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
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::__construct
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::getOptions
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::getUserProvider
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::getLogger
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
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::getKey
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::setKey
     */
    public function testSetKey()
    {
        $service = $this->getService();

        $this->assertNull($service->getKey());
        $service->setKey('foo');
        $this->assertEquals('foo', $service->getKey());
    }

    /**
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::getTokenProvider
     * @covers Symfony\Component\HttpKernel\Security\RememberMe\PersistentTokenBasedRememberMeServices::setTokenProvider
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
     * @expectedException Symfony\Component\Security\Exception\AuthenticationException
     * @expectedMessage The cookie is invalid.
     */
    public function testAutoLoginThrowsExceptionOnInvalidCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request;
        $request->request->set('foo', 'true');
        $request->cookies->set('foo', 'foo');

        $service->autoLogin($request);
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\TokenNotFoundException
     */
    public function testAutoLoginThrowsExceptionOnNonExistentToken()
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request;
        $request->request->set('foo', 'true');
        $request->cookies->set('foo', $this->encodeCookie(array(
            $series = 'fooseries',
            $tokenValue = 'foovalue',
        )));

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->throwException(new TokenNotFoundException('Token not found.')))
        ;
        $service->setTokenProvider($tokenProvider);

        $service->autoLogin($request);
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\UsernameNotFoundException
     */
    public function testAutoLoginThrowsExceptionOnNonExistentUser()
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, array('name' => 'foo', 'always_remember_me' => true, 'lifetime' => 3600));
        $request = new Request;
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->returnValue(new PersistentToken('fooname', 'fooseries', 'foovalue', new \DateTime())))
        ;
        $service->setTokenProvider($tokenProvider);

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->will($this->throwException(new UsernameNotFoundException('user not found')))
        ;

        $service->autoLogin($request);
    }

    public function testAutoLoginThrowsExceptionOnStolenCookieAndRemovesItFromThePersistentBackend()
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, array('name' => 'foo', 'always_remember_me' => true));
        $request = new Request;
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $service->setTokenProvider($tokenProvider);

        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->returnValue(new PersistentToken('foouser', 'fooseries', 'anotherFooValue', new \DateTime())))
        ;

        $tokenProvider
            ->expects($this->once())
            ->method('deleteTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(null))
        ;

        try {
            $service->autoLogin($request);
        } catch (CookieTheftException $theft) {
            return;
        }

        $this->fail('Expected CookieTheftException was not thrown.');
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\AuthenticationException
     * @expectedMessage The cookie has expired.
     */
    public function testAutoLoginDoesNotAcceptAnExpiredCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'always_remember_me' => true, 'lifetime' => 3600));
        $request = new Request;
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(new PersistentToken('username', 'fooseries', 'newFooValue', new \DateTime('yesterday'))))
        ;
        $service->setTokenProvider($tokenProvider);

        $service->autoLogin($request);
    }

    public function testAutoLogin()
    {
        $user = $this->getMock('Symfony\Component\Security\User\AccountInterface');
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array('ROLE_FOO')))
        ;

        $userProvider = $this->getProvider();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo('foouser'))
            ->will($this->returnValue($user))
        ;

        $service = $this->getService($userProvider, array('name' => 'foo', 'always_remember_me' => true, 'lifetime' => 3600));
        $service->setKey('fookey');
        $request = new Request;
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(new PersistentToken('foouser', 'fooseries', 'foovalue', new \DateTime())))
        ;
        $service->setTokenProvider($tokenProvider);

        $returnedToken = $service->autoLogin($request);

        $this->assertInstanceOf('Symfony\Component\Security\Authentication\Token\RememberMeToken', $returnedToken);
        $this->assertInstanceOf('Symfony\Component\Security\Authentication\RememberMe\PersistentTokenInterface', $returnedToken->getPersistentToken());
        $this->assertSame($user, $returnedToken->getUser());
        $this->assertEquals('fookey', $returnedToken->getKey());
    }

    public function testLogout()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));
        $response = new Response();
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('deleteTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(null))
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertFalse($response->headers->hasCookie('foo'));

        $service->logout($request, $response, $token);

        $this->assertTrue($response->headers->getCookie('foo')->isCleared());
    }

    public function testLogoutSimplyIgnoresNonSetRequestCookie()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request;
        $response = new Response;
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->never())
            ->method('deleteTokenBySeries')
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertFalse($response->headers->hasCookie('foo'));
        $service->logout($request, $response, $token);

        $this->assertTrue($response->headers->getCookie('foo')->isCleared());
    }

    public function testLogoutSimplyIgnoresInvalidCookie()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request;
        $request->cookies->set('foo', 'somefoovalue');
        $response = new Response;
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->never())
            ->method('deleteTokenBySeries')
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertFalse($response->headers->hasCookie('foo'));

        $service->logout($request, $response, $token);

        $this->assertTrue($response->headers->getCookie('foo')->isCleared());
    }

    public function testLoginFail()
    {
        $service = $this->getService(null, array('name' => 'foo'));
        $request = new Request();
        $response = new Response();

        $this->assertFalse($response->headers->hasCookie('foo'));

        $service->loginFail($request, $response);

        $this->assertTrue($response->headers->getCookie('foo')->isCleared());
    }

    public function testLoginSuccessRenewsRememberMeTokenWhenUsedForLogin()
    {
        $service = $this->getService(null, array('name' => 'foo', 'domain' => 'myfoodomain.foo', 'path' => '/foo/path', 'secure' => true, 'httponly' => true, 'lifetime' => 3600));
        $request = new Request;
        $response = new Response;

        $user = $this->getMock('Symfony\Component\Security\User\AccountInterface');
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array('ROLE_FOO')))
        ;

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\RememberMeToken', array(), array($user, 'fookey'));
        $token
            ->expects($this->once())
            ->method('getPersistentToken')
            ->will($this->returnValue(new PersistentToken('foouser', 'fooseries', 'foovalue', new \DateTime())))
        ;

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('updateToken')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(null))
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertFalse($response->headers->hasCookie('foo'));

        $service->loginSuccess($request, $response, $token);

        $cookie = $response->headers->getCookie('foo');
        $this->assertFalse($cookie->isCleared());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttponly());
        $this->assertTrue($cookie->getExpire() > time() + 3590 && $cookie->getExpire() < time() + 3610);
        $this->assertEquals('myfoodomain.foo', $cookie->getDomain());
        $this->assertEquals('/foo/path', $cookie->getPath());
    }

    /**
     * @expectedException RuntimeException
     * @expectedMessage RememberMeToken must contain a PersistentTokenInterface implementation when used as login.
     */
    public function testLoginSuccessThrowsExceptionWhenRememberMeTokenDoesNotContainPersistentTokenImplementation()
    {
        $service = $this->getService(null, array('always_remember_me' => true, 'name' => 'foo'));
        $request = new Request;
        $response = new Response;

        $user = $this->getMock('Symfony\Component\Security\User\AccountInterface');
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array('ROLE_FOO')))
        ;

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\RememberMeToken', array(), array($user, 'fookey'));
        $token
            ->expects($this->once())
            ->method('getPersistentToken')
            ->will($this->returnValue(null))
        ;

        $service->loginSuccess($request, $response, $token);
    }

    public function testLoginSuccessSetsCookieWhenLoggedInWithAnotherTokenInterfaceImplementation()
    {
        $service = $this->getService(null, array('name' => 'foo', 'domain' => 'myfoodomain.foo', 'path' => '/foo/path', 'secure' => true, 'httponly' => true, 'lifetime' => 3600, 'always_remember_me' => true));
        $request = new Request;
        $response = new Response;

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('foouser'))
        ;

        $tokenProvider = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\TokenProviderInterface');
        $tokenProvider
            ->expects($this->once())
            ->method('createNewToken')
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertFalse($response->headers->hasCookie('foo'));

        $service->loginSuccess($request, $response, $token);

        $cookie = $response->headers->getCookie('foo');
        $this->assertFalse($cookie->isCleared());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttponly());
        $this->assertTrue($cookie->getExpire() > time() + 3590 && $cookie->getExpire() < time() + 3610);
        $this->assertEquals('myfoodomain.foo', $cookie->getDomain());
        $this->assertEquals('/foo/path', $cookie->getPath());
    }

    protected function encodeCookie(array $parts)
    {
        $service = $this->getService();
        $r = new \ReflectionMethod($service, 'encodeCookie');
        $r->setAccessible(true);

        return $r->invoke($service, $parts);
    }

    protected function getService($userProvider = null, $options = array(), $logger = null)
    {
        if (null === $userProvider) {
            $userProvider = $this->getProvider();
        }

        return new PersistentTokenBasedRememberMeServices($userProvider, $options, $logger);
    }

    protected function getProvider()
    {
        $provider = $this->getMock('Symfony\Component\Security\User\UserProviderInterface');

        return $provider;
    }
}
