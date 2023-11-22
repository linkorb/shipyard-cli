<?php

namespace LinkORB\Shipyard\Command;

use LinkORB\Shipyard\Model\Config;
use LinkORB\Shipyard\Shipyard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApplyCommand extends Command
{


    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists('shipyard.yaml')) {
            throw new \RuntimeException('shipyard.yaml file not found.');
        }

        $objectNormalizer = new ObjectNormalizer(null, null, null, new ReflectionExtractor());
        $serializer = new Serializer(
            [$objectNormalizer, new ArrayDenormalizer(), new GetSetMethodNormalizer()],
            [new YamlEncoder()],
        );

        $config = $serializer->deserialize(file_get_contents('shipyard.yaml'), Config::class, 'yaml');

        $shipyard = new Shipyard($config, $output);

        $shipyard->apply();

        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->setName('apply')
            ->setDescription('Shipyard apply');
    }

}
