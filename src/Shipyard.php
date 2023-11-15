<?php

namespace LinkORB\Shipyard;

use LinkORB\Shipyard\Model\Config;
use Symfony\Component\Console\Output\OutputInterface;

class Shipyard
{
    private string $chartsPath = 'shipyard/charts';            // Default chart path: {cwd}/shipyard/charts
    private string $stackPath = 'opt/shipyard/stacks';         // Default `opt/shipyard/stacks`. Stacks path on

    /**
     * Read and validate the file `shipyard.yaml` and save the values extracted.
     */
    public function __construct(private readonly Config $config, private readonly OutputInterface $output)
    {
        if (!$this->config->hasStacks()) {
            throw new \RuntimeException('No stacks found in shipyard.yaml.');
        }

        if (!$this->config->getSettings()?->getChartsPath()) {
            $this->config->getSettings()->setChartsPath($this->chartsPath);
        }
        if (!$this->config->getSettings()?->getStackPath()) {
            $this->config->getSettings()->setStackPath($this->stackPath);
        }
    }

    /**
     * Run the stacks of the shipyard.
     */
    public function apply()
    {
        foreach ($this->config->getStacks() as $stack) {
            $obj = new Stack($this->config, $stack->getName(), $this->output);
            $obj->run();
        }
    }
}
