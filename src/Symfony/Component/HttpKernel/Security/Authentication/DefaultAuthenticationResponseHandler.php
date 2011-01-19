<?php

namespace Symfony\Component\HttpKernel\Security\Authentication;

use Symfony\Component\Security\SecurityContext;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

class DefaultAuthenticationResponseHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    protected $options;
    protected $logger;

    public function __construct(array $options = array(), LoggerInterface $logger = null)
    {
        $this->options = array_merge(array(
            'login_path'                     => '/login',
            'always_use_default_target_path' => false,
            'default_target_path'            => '/',
            'target_path_parameter'          => '_target_path',
            'use_referer'                    => false,
            'failure_path'                   => null,
            'failure_forward'                => false,
        ), $options);
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(Event $event, Request $request, TokenInterface $token)
    {
        $response = new Response();
        $path = $this->determineTargetUrl($request);
        $response->setRedirect(0 !== strpos($path, 'http') ? $request->getUriForPath($path) : $path, 302);

        return $response;
    }

    public function onAuthenticationFailure(Event $event, Request $request, \Exception $exception)
    {
        if (null === $this->options['failure_path']) {
            $this->options['failure_path'] = $this->options['login_path'];
        }

        if ($this->options['failure_forward']) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Forwarding to %s', $this->options['failure_path']));
            }

            $subRequest = Request::create($this->options['failure_path']);
            $subRequest->attributes->set(SecurityContext::AUTHENTICATION_ERROR, $failed->getMessage());

            return $event->getSubject()->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } else {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Redirecting to %s', $this->options['failure_path']));
            }

            $request->getSession()->set(SecurityContext::AUTHENTICATION_ERROR, $failed->getMessage());

            $response = new Response();
            $response->setRedirect(0 !== strpos($this->options['failure_path'], 'http') ? $request->getUriForPath($this->options['failure_path']) : $this->options['failure_path'], 302);

            return $response;
        }
    }

    /**
     * Builds the target URL according to the defined options.
     */
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        if ($targetUrl = $request->get($this->options['target_path_parameter'])) {
            return $targetUrl;
        }

        $session = $request->getSession();
        if ($targetUrl = $session->get('_security.target_path')) {
            $session->remove('_security.target_path');

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->headers->get('Referer')) {
            return $targetUrl;
        }

        return $this->options['default_target_path'];
    }
}