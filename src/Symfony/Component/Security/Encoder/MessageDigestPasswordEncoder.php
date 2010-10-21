<?php

namespace Symfony\Component\Security\Encoder;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MessageDigestPasswordEncoder uses a message digest algorithm.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class MessageDigestPasswordEncoder extends BasePasswordEncoder
{
    protected $algorithm;
    protected $encodeHashAsBase64;

    /**
     * Constructor.
     *
     * @param string  $algorithm          The digest algorithm to use
     * @param Boolean $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param integer $iterations         The number of iterations to use to stretch the password
     */
    public function __construct($algorithm = 'sha256', $encodeHashAsBase64 = false, $iterations = 1)
    {
        $this->algorithm = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations = $iterations;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        $salted = $this->mergePasswordAndSalt($raw, $salt);
        $digest = $this->callAlgorithm($this->algorithm, $salted, $this->iterations);

        return $this->encodeHashAsBase64 ? base64_encode($digest) : $digest;
    }
    
    /**
     * Calls the given algorithm and returns the hashed result
     * 
     * @param string $algorithm The name of the algorithm to call
     * @param string $raw The input to perform the algorith on
     * @param integer $iterations The number of times to perform the algorithm
     * @return string
     */
    protected function callAlgorithm($algorithm, $raw, $iterations = 1)
    {
        if (in_array($algorithm, hash_algos(), true)) {
        	  $digest = hash($algorithm, $raw);
        	
            for ($i = 1; $i < $iterations; $i++) {
            	  $digest = hash($algorithm, $digest);
            }
            
            return $digest;
        }
        
        if (function_exists($algorithm)) {
        	  $digest = $algorithm($raw);
        	
        	  for ($i = 1; $i < $iterations; $i++) {
        		    $digest = $algorithm($digest);
        	  }
        	
        	  return $digest;
        }
        
 		    throw new \LogicException(sprintf('The algorithm "%s" is not supported.', $algorithm));
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }
}
