<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Warmup the cache.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class CacheClearCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clears the cache.')
            ->addOption('warmup', null, InputOption::VALUE_NONE, 'Warmup the cache immediately after clearing it.')
            ->setHelp(<<<EOF
The <info>cache:clear</info> command clear the cache.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Clearing the cache</comment>');

        $finder = new Finder();
        $files = $finder->files()->in($this->container->getParameter('kernel.cache_dir'));
        $files = array_keys(iterator_to_array($files));
        foreach ($files as $file) {
            $output->writeln(sprintf(' - Deleting <info>%s</info>', $file));
        }
        $fs = $this->container->get('filesystem');
        $fs->remove($files);

        if ($input->getOption('warmup')) {
            $output->writeln('<comment>Warming up the cache</comment>');

            $warmer = $this->container->get('cache_warmer');
            $warmer->enableOptionalWarmers();
            $warmer->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }
}
