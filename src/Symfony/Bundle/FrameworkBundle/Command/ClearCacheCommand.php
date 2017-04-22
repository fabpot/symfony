<?php

namespace Symfony\Bundle\FrameworkBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Util\Filesystem;

/**
 * ClearCache command as the one in Symfony1
 *
 * @author Henrik Bjornskov <hb@peytz.dk>
 */
class ClearCacheCommand extends Command
{
    /**
     * Configurations
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('environment', InputArgument::OPTIONAL, 'Environment name (dev|test|prod)'),
            ))
            ->setName('app:clear-cache')
            ->setDescription('Clears the cache folder for a given environment.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getArgument('environment') ? $input->getArgument('environment') : 'dev';

        if (!in_array($environment, array('dev', 'prod', 'test'))) {
            throw new \InvalidArgumentException('Invalid environment name specified. Availible names are [dev, test, prod]');
        }

        $cacheDir = realpath($this->container->getParameter('kernel.cache_dir') . '/../' . $environment);
        $filesystem = new Filesystem();

        // Removes the directory
        $filesystem->remove($cacheDir);

        // Recreates the cache directory
        $filesystem->mkdirs($cacheDir);

        $output->writeln(sprintf('Cleared cache directory <info>%s</info> for environment <info>%s</info>', $cacheDir, $environment));
    }
}
