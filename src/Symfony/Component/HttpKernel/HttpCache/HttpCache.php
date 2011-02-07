<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This code is partially based on the Rack-Cache library by Ryan Tomayko,
 * which is released under the MIT license.
 * (based on commit 02d2b48d75bcb63cf1c0c7149c077ad256542801)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\HttpCache;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache provides HTTP caching.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class HttpCache implements HttpKernelInterface
{
    protected $kernel;
    protected $traces;
    protected $store;
    protected $request;
    protected $esi;
    protected $esiTtls;

    /**
     * Constructor.
     *
     * The available options are:
     *
     *   * debug:                 If true, the traces are added as a HTTP header to ease debugging
     *
     *   * default_ttl            The number of seconds that a cache entry should be considered
     *                            fresh when no explicit freshness information is provided in
     *                            a response. Explicit Cache-Control or Expires headers
     *                            override this value. (default: 0)
     *
     *   * private_headers        Set of request headers that trigger "private" cache-control behavior
     *                            on responses that don't explicitly state whether the response is
     *                            public or private via a Cache-Control directive. (default: Authorization and Cookie)
     *
     *   * allow_reload           Specifies whether the client can force a cache reload by including a
     *                            Cache-Control "no-cache" directive in the request. Set it to ``true``
     *                            for compliance with RFC 2616. (default: false)
     *
     *   * allow_revalidate       Specifies whether the client can force a cache revalidate by including
     *                            a Cache-Control "max-age=0" directive in the request. Set it to ``true``
      *                            for compliance with RFC 2616. (default: false)
     *
     *   * stale_while_revalidate Specifies the default number of seconds (the granularity is the second as the
     *                            Response TTL precision is a second) during which the cache can immediately return
     *                            a stale response while it revalidates it in the background (default: 2).
     *                            This setting is overridden by the stale-while-revalidate HTTP Cache-Control
     *                            extension (see RFC 5861).
     *
     *   * stale_if_error         Specifies the default number of seconds (the granularity is the second) during which
     *                            the cache can serve a stale response when an error is encountered (default: 60).
     *                            This setting is overridden by the stale-if-error HTTP Cache-Control extension
     *                            (see RFC 5861).
     *
     * @param HttpKernelInterface $kernel An HttpKernelInterface instance
     * @param StoreInterface      $store  A Store instance
     * @param Esi                 $esi    An Esi instance
     * @param array                                             $options        An array of options
     */
    public function __construct(HttpKernelInterface $kernel, StoreInterface $store, Esi $esi = null, array $options = array())
    {
        $this->store = $store;
        $this->kernel = $kernel;

        // needed in case there is a fatal error because the backend is too slow to respond
        register_shutdown_function(array($this->store, 'cleanup'));

        $this->options = array_merge(array(
            'debug'                  => false,
            'default_ttl'            => 0,
            'private_headers'        => array('Authorization', 'Cookie'),
            'allow_reload'           => false,
            'allow_revalidate'       => false,
            'stale_while_revalidate' => 2,
            'stale_if_error'         => 60,
        ), $options);
        $this->esi = $esi;
    }

    /**
     * Returns an array of events that took place during processing of the last request.
     *
     * @return array An array of events
     */
    public function getTraces()
    {
        return $this->traces;
    }

    /**
     * Returns a log message for the events of the last request processing.
     *
     * @return string A log message
     */
    public function getLog()
    {
        $log = array();
        foreach ($this->traces as $request => $traces) {
            $log[] = sprintf('%s: %s', $request, implode(', ', $traces));
        }

        return implode('; ', $log);
    }

    /**
     * Gets the Request instance associated with the master request.
     *
     * @return Symfony\Component\HttpFoundation\Request A Request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // FIXME: catch exceptions and implement a 500 error page here? -> in Varnish, there is a built-in error page mechanism
        if (HttpKernelInterface::MASTER_REQUEST === $type) {
            $this->traces = array();
            $this->request = $request;
            $this->esiTtls = array();
        }

        $path = $request->getPathInfo();
        if ($qs = $request->getQueryString()) {
            $path .= '?'.$qs;
        }
        $this->traces[$request->getMethod().' '.$path] = array();

        if (!$request->isMethodSafe($request)) {
            $response = $this->invalidate($request, $catch);
        } elseif ($request->headers->has('expect')) {
            $response = $this->pass($request, $catch);
        } else {
            $response = $this->lookup($request, $catch);
        }

        $response->isNotModified($request);

        $this->restoreResponseBody($request, $response);

        if (HttpKernelInterface::MASTER_REQUEST === $type && $this->options['debug']) {
            $response->headers->set('X-Symfony-Cache', $this->getLog());
        }

        if (null !== $this->esi) {
            $this->addEsiTtl($response);

            if ($request === $this->request) {
                $this->updateResponseCacheControl($response);
            }
        }

        return $response;
    }

    /**
     * Stores the response's TTL locally.
     *
     * @param Response $response
     */
    protected function addEsiTtl(Response $response)
    {
        $this->esiTtls[] = $response->isValidateable() ? 0 : $response->getTtl();
    }

    /**
     * Changes the master response TTL to the smallest TTL received or force validation if
     * one of the ESI has validation cache strategy.
     *
     * @param Response $response
     */
    protected function updateResponseCacheControl(Response $response)
    {
        $ttl = min($this->esiTtls);
        if (0 === $ttl) {
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        } else {
            $response->setSharedMaxAge($ttl);
        }
    }

    /**
     * Forwards the Request to the backend without storing the Response in the cache.
     *
     * @param Request $request A Request instance
     * @param Boolean  $catch   whether to process exceptions
     *
     * @return Response A Response instance
     */
    protected function pass(Request $request, $catch = false)
    {
        $this->record($request, 'pass');

        return $this->forward($request, $catch);
    }

    /**
     * Invalidates non-safe methods (like POST, PUT, and DELETE).
     *
     * @param Request $request A Request instance
     * @param Boolean  $catch   whether to process exceptions
     *
     * @return Response A Response instance
     *
     * @see RFC2616 13.10
     */
    protected function invalidate(Request $request, $catch = false)
    {
        $response = $this->pass($request, $catch);

        // invalidate only when the response is successful
        if ($response->isSuccessful() || $response->isRedirect()) {
            try {
                $this->store->invalidate($request, $catch);

                $this->record($request, 'invalidate');
            } catch (\Exception $e) {
                $this->record($request, 'invalidate-failed');

                if ($this->options['debug']) {
                    throw $e;
                }
            }
        }

        return $response;
    }

    /**
     * Lookups a Response from the cache for the given Request.
     *
     * When a matching cache entry is found and is fresh, it uses it as the
     * response without forwarding any request to the backend. When a matching
     * cache entry is found but is stale, it attempts to "validate" the entry with
     * the backend using conditional GET. When no matching cache entry is found,
     * it triggers "miss" processing.
     *
     * @param Request $request A Request instance
     * @param Boolean  $catch   whether to process exceptions
     *
     * @return Response A Response instance
     */
    protected function lookup(Request $request, $catch = false)
    {
        // if allow_reload and no-cache Cache-Control, allow a cache reload
        if ($this->options['allow_reload'] && $request->isNoCache()) {
            $this->record($request, 'reload');

            return $this->fetch($request);
        }

        try {
            $entry = $this->store->lookup($request);
        } catch (\Exception $e) {
            $this->record($request, 'lookup-failed');

            if ($this->options['debug']) {
                throw $e;
            }

            return $this->pass($request, $catch);
        }

        if (null === $entry) {
            $this->record($request, 'miss');

            return $this->fetch($request, $catch);
        }

        if (!$this->isFreshEnough($request, $entry)) {
            $this->record($request, 'stale');

            return $this->validate($request, $entry);
        }

        $this->record($request, 'fresh');

        $entry->headers->set('Age', $entry->getAge());

        return $entry;
    }

    /**
     * Validates that a cache entry is fresh.
     *
     * The original request is used as a template for a conditional
     * GET request with the backend.
     *
     * @param Request  $request A Request instance
     * @param Response $entry A Response instance to validate
     *
     * @return Response A Response instance
     */
    protected function validate(Request $request, Response $entry)
    {
        $subRequest = clone $request;

        // send no head requests because we want content
        $subRequest->setMethod('get');

        // add our cached last-modified validator
        $subRequest->headers->set('if_modified_since', $entry->headers->get('Last-Modified'));

        // Add our cached etag validator to the environment.
        // We keep the etags from the client to handle the case when the client
        // has a different private valid entry which is not cached here.
        $cachedEtags = array($entry->getEtag());
        $requestEtags = $request->getEtags();
        $etags = array_unique(array_merge($cachedEtags, $requestEtags));
        $subRequest->headers->set('if_none_match', $etags ? implode(', ', $etags) : '');

        $response = $this->forward($subRequest, false, $entry);

        if (304 == $response->getStatusCode()) {
            $this->record($request, 'valid');

            // return the response and not the cache entry if the response is valid but not cached
            $etag = $response->getEtag();
            if ($etag && in_array($etag, $requestEtags) && !in_array($etag, $cachedEtags)) {
                return $response;
            }

            $entry = clone $entry;
            $entry->headers->remove('Date');

            foreach (array('Date', 'Expires', 'Cache-Control', 'ETag', 'Last-Modified') as $name) {
                if ($response->headers->has($name)) {
                    $entry->headers->set($name, $response->headers->get($name));
                }
            }

            $response = $entry;
        } else {
            $this->record($request, 'invalid');
        }

        if ($response->isCacheable()) {
            $this->store($request, $response);
        }

        return $response;
    }

    /**
     * Forwards the Request to the backend and determines whether the response should be stored.
     *
     * This methods is trigered when the cache missed or a reload is required.
     *
     * @param Request  $request A Request instance
     * @param Boolean  $catch   whether to process exceptions
     *
     * @return Response A Response instance
     */
    protected function fetch(Request $request, $catch = false)
    {
        $subRequest = clone $request;

        // send no head requests because we want content
        $subRequest->setMethod('get');

        // avoid that the backend sends no content
        $subRequest->headers->remove('if_modified_since');
        $subRequest->headers->remove('if_none_match');

        $response = $this->forward($subRequest, $catch);

        if ($this->isPrivateRequest($request) && !$response->headers->hasCacheControlDirective('public')) {
            $response->setPrivate(true);
        } elseif ($this->options['default_ttl'] > 0 && null === $response->getTtl() && !$response->headers->getCacheControlDirective('must-revalidate')) {
            $response->setTtl($this->options['default_ttl']);
        }

        if ($response->isCacheable()) {
            $this->store($request, $response);
        }

        return $response;
    }

    /**
     * Forwards the Request to the backend and returns the Response.
     *
     * @param Request  $request  A Request instance
     * @param Boolean  $catch    Whether to catch exceptions or not
     * @param Response $response A Response instance (the stale entry if present, null otherwise)
     *
     * @return Response A Response instance
     */
    protected function forward(Request $request, $catch = false, Response $entry = null)
    {
        if ($this->esi) {
            $this->esi->addSurrogateEsiCapability($request);
        }

        // always a "master" request (as the real master request can be in cache)
        $response = $this->kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, $catch);
        // FIXME: we probably need to also catch exceptions if raw === true

        // we don't implement the stale-if-error on Requests, which is nonetheless part of the RFC
        if (null !== $entry && in_array($response->getStatusCode(), array(500, 502, 503, 504))) {
            if (null === $age = $entry->headers->getCacheControlDirective('stale-if-error')) {
                $age = $this->options['stale_if_error'];
            }

            if (abs($entry->getTtl()) < $age) {
                $this->record($request, 'stale-if-error');

                return $entry;
            }
        }

        $this->processResponseBody($request, $response);

        return $response;
    }

    /**
     * Checks whether the cache entry is "fresh enough" to satisfy the Request.
     *
     * @param Request  $request A Request instance
     * @param Response $entry   A Response instance
     *
     * @return Boolean true if the cache entry if fresh enough, false otherwise
     */
    protected function isFreshEnough(Request $request, Response $entry)
    {
        if (!$entry->isFresh()) {
            return $this->lock($request, $entry);
        }

        if ($this->options['allow_revalidate'] && null !== $maxAge = $request->headers->getCacheControlDirective('max-age')) {
            return $maxAge > 0 && $maxAge >= $entry->getAge();
        }

        return true;
    }

    /**
     * Locks a Request during the call to the backend.
     *
     * @param Request  $request A Request instance
     * @param Response $entry   A Response instance
     *
     * @return Boolean true if the cache entry can be returned even if it is staled, false otherwise
     */
    protected function lock(Request $request, Response $entry)
    {
        // try to acquire a lock to call the backend
        $lock = $this->store->lock($request, $entry);

        // there is already another process calling the backend
        if (true !== $lock) {
            // check if we can serve the stale entry
            if (null === $age = $entry->headers->getCacheControlDirective('stale-while-revalidate')) {
                $age = $this->options['stale_while_revalidate'];
            }

            if (abs($entry->getTtl()) < $age) {
                $this->record($request, 'stale-while-revalidate');

                // server the stale response while there is a revalidation
                return true;
            } else {
                // wait for the lock to be released
                $wait = 0;
                while (file_exists($lock) && $wait < 5000000) {
                    usleep($wait += 50000);
                }

                if ($wait < 2000000) {
                    // replace the current entry with the fresh one
                    $new = $this->lookup($request);
                    $entry->headers = $new->headers;
                    $entry->setContent($new->getContent());
                    $entry->setStatusCode($new->getStatusCode());
                    $entry->setProtocolVersion($new->getProtocolVersion());
                    $entry->setCookies($new->getCookies());

                    return true;
                } else {
                    // backend is slow as hell, send a 503 response (to avoid the dog pile effect)
                    $entry->setStatusCode(503);
                    $entry->setContent('503 Service Unavailable');
                    $entry->headers->set('Retry-After', 10);

                    return true;
                }
            }
        }

        // we have the lock, call the backend
        return false;
    }

    /**
     * Writes the Response to the cache.
     *
     * @param Request  $request  A Request instance
     * @param Response $response A Response instance
     */
    protected function store(Request $request, Response $response)
    {
        try {
            $this->store->write($request, $response);

            $this->record($request, 'store');

            $response->headers->set('Age', $response->getAge());
        } catch (\Exception $e) {
            $this->record($request, 'store-failed');

            if ($this->options['debug']) {
                throw $e;
            }
        }

        // now that the response is cached, release the lock
        $this->store->unlock($request);
    }

    /**
     * Restores the Response body.
     *
     * @param Response $response A Response instance
     *
     * @return Response A Response instance
     */
    protected function restoreResponseBody(Request $request, Response $response)
    {
        if ('head' === strtolower($request->getMethod())) {
            $response->setContent('');
            $response->headers->remove('X-Body-Eval');
            $response->headers->remove('X-Body-File');

            return;
        }

        if ($response->headers->has('X-Body-Eval')) {
            ob_start();

            if ($response->headers->has('X-Body-File')) {
                include $response->headers->get('X-Body-File');
            } else {
                eval('; ?>'.$response->getContent().'<?php ;');
            }

            $response->setContent(ob_get_clean());
            $response->headers->remove('X-Body-Eval');
        } elseif ($response->headers->has('X-Body-File')) {
            $response->setContent(file_get_contents($response->headers->get('X-Body-File')));
        } else {
            return;
        }

        $response->headers->remove('X-Body-File');

        if (!$response->headers->has('Transfer-Encoding')) {
            $response->headers->set('Content-Length', strlen($response->getContent()));
        }
    }

    protected function processResponseBody(Request $request, Response $response)
    {
        if (null !== $this->esi && $this->esi->needsEsiParsing($response)) {
            $this->esi->process($request, $response);
        }
    }

    /**
     * Checks if the Request includes authorization or other sensitive information
     * that should cause the Response to be considered private by default.
     *
     * @param Request $request A Request instance
     *
     * @return Boolean true if the Request is private, false otherwise
     */
    protected function isPrivateRequest(Request $request)
    {
        foreach ($this->options['private_headers'] as $key) {
            $key = strtolower(str_replace('HTTP_', '', $key));

            if ('cookie' === $key) {
                if (count($request->cookies->all())) {
                    return true;
                }
            } elseif ($request->headers->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Records that an event took place.
     *
     * @param string $event The event name
     */
    protected function record(Request $request, $event)
    {
        $path = $request->getPathInfo();
        if ($qs = $request->getQueryString()) {
            $path .= '?'.$qs;
        }
        $this->traces[$request->getMethod().' '.$path][] = $event;
    }
}
