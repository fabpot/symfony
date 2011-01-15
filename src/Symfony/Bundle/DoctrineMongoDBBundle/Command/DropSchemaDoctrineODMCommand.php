<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Command to create the database schema for a set of classes based on their mappings.
 *
 * @author     Justin Hileman <justin@shopopensky.com>
 */
class DropSchemaDoctrineODMCommand extends DropCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:schema:drop')
            ->addOption('dm', null, InputOption::VALUE_REQUIRED, 'The document manager to use for this command.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:schema:drop</info> command drops the default document manager's schema:

  <info>./symfony doctrine:mongodb:schema:drop</info>

You can also optionally specify the name of a document manager to drop the schema for:

  <info>./symfony doctrine:mongodb:schema:drop --dm=default</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->application, $input->getOption('dm'));

        parent::execute($input, $output);
    }
}