<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMigrationsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;

/**
 * Command for executing a migration to a specified version or the latest available version.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class MigrationsMigrateDoctrineCommand extends MigrateCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineCommand::setApplicationEntityManager($this->application, $input->getOption('em'));

        $configuration = $this->_getMigrationConfiguration($input, $output);
        DoctrineCommand::configureMigrationsForBundle($this->application, $input->getOption('bundle'), $configuration);

        parent::execute($input, $output);
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:migrations:migrate')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'The bundle to load migrations configuration from.')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
        ;
    }
}