<?php

namespace LinkORB\Shipyard;

use LinkORB\Shipyard\Stack;

class Shipyard
{

    private $chartsPath = 'shipyard/charts';            // Default chart path: {cwd}/shipyard/charts
    private $stackPath = 'opt/shipyard/stacks';         // Default stacks path on remote host or local
    private $stacks = NULL;
    private $output = NULL;

    public function __construct($yaml, $output)
    {
        if (array_key_exists('settings', $yaml)) {
            if (array_key_exists('charts_path', $yaml['settings'])) {
                $this->chartsPath = $yaml['settings']['charts_path'];
            }
            if (array_key_exists('stack_path', $yaml['settings'])) {
                $this->stackPath = $yaml['settings']['stack_path'];
            }
        }

        if (array_key_exists('stacks', $yaml)) {
            $this->stacks = $yaml['stacks'];
        } else {
            throw new \RuntimeException('No stacks found in shipyard.yaml.');
        }

        $this->output = $output;
    }

    public function apply()
    {
        foreach ($this->stacks as $s) {
            $obj = new Stack($s, $this->chartsPath, $this->stackPath, $this->output);
            $obj->run();
        }
    }
}
