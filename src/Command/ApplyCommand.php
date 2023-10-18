<?php

namespace LinkORB\Shipyard\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use LinkORB\Shipyard\Shipyard;

class ApplyCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('apply')
            ->setDescription('Shipyard apply');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists('shipyard.yaml')) {
            throw new \RuntimeException('shipyard.yaml file not found.');
        }

        $yaml = Yaml::parseFile('shipyard.yaml');

        $shipyard = new Shipyard($yaml, $output);

        $shipyard->apply();

        return Command::SUCCESS;
    }

}
