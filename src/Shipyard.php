<?php

namespace LinkORB\Shipyard;

use LinkORB\Shipyard\Model\Shipyard as ShipyardModel;
use Symfony\Component\Console\Output\OutputInterface;

class Shipyard
{
    /**
     * Read and validate the file `shipyard.yaml` and save the values extracted.
     */
    public function __construct(private readonly ShipyardModel $model, private readonly OutputInterface $output)
    {
        if (!$this->model->hasStacks()) {
            throw new \RuntimeException('No stacks found in shipyard.yaml.');
        }
    }

    /**
     * Run the stacks of the shipyard.
     */
    public function apply()
    {
        foreach ($this->model->getStacks() as $stack) {
            $obj = new Stack($this->model, $stack->getName(), $this->output);
            $obj->run();
        }
    }
}
