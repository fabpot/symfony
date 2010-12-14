<?php

namespace Symfony\Component\DependencyInjection\Compiler;

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