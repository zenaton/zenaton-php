<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Zenaton\Worker\Configuration;

class StatusCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('List all the subscribed application of a worker.')
            ->setHelp('This command allows you to list the application currently subscribed by a worker.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feedback = (new Configuration())->status();
        foreach ($feedback->msg as $f) {
            $output->writeln($f);
        }
        return;
    }
}
