<?php

namespace LinkORB\Shipyard;

use LinkORB\Shipyard\Stack;

class Shipyard
{

    private $charts_path = 'shipyard/charts';            // Default chart path: {cwd}/shipyard/charts
    private $stacks = NULL;
    private $output = NULL;

    public function __construct($yaml, $output)
    {
        if (array_key_exists('settings', $yaml)) {
            if (array_key_exists('charts_path', $yaml['settings'])) {
                $this->charts_path = $yaml['settings']['charts_path'];
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
            $obj = new Stack($s, $this->charts_path, $this->output);
            $obj->run();
        }
    }
}
