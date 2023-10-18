<?php

namespace LinkORB\Shipyard;

use Symfony\Component\Yaml\Yaml;

class Stack
{

    private $config = NULL;
    private $output = NULL;
    private $stacks_path = NULL;

    public function __construct($config, $stacks_path, $output)
    {
        // # Config example values
        // name: my-whoami
        // chart: whoami
        // host: swarm-host-a
        // values: my-whoami/values.yaml

        $this->config = $config;

        $this->stacks_path = $stacks_path;
        $this->output = $output;
    }

    public function run()
    {
        $this->output->writeln(sprintf('Stack run `%s(%s)`', $this->config['name'], $this->config['host']));
        $this->load_values();
    }

    private function load_values()
    {
        if (!array_key_exists('values', $this->config)) {
            throw new \RuntimeException('Stack values not found.');
        }

        if (!file_exists($this->config['values'])) {
            throw new \RuntimeException(sprintf('%s file not found.', $this->config['values']));
        }

    }
}
