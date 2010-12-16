<?php

namespace Symfony\Component\DependencyInjection\Compiler;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Holds the compilation pass configuration
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PassConfig
{
    protected $passes;
    
    public function __construct()
    {
        $passes = array();
        $passes[] = new MergeExtensionConfigurationPass();
        $passes[] = new ResolveInterfaceInjectorsPass();
        
        $this->passes = $passes;
    }

    public function addPass(CompilerPassInterface $pass)
    {
        $this->passes[] = $pass;
    }

    public function getPasses()
    {
        return $this->passes;
    }

    public function setPasses(array $passes)
    {
        $this->passes = $passes;
    }
}