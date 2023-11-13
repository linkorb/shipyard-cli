<?php

namespace LinkORB\Shipyard;

use LinkORB\Shipyard\Stack;

class Shipyard
{

    private $chartsPath = 'shipyard/charts';            // Default chart path: {cwd}/shipyard/charts
    private $stackPath = 'opt/shipyard/stacks';         // Default `opt/shipyard/stacks`. Stacks path on remote host or local.
    private $stacks = NULL;
    private $output = NULL;                             // Symfony CLI ouput
    private $tag = NULL;

    /**
     * Constructor.
     * Read and validate the file `shipyard.yaml` and save the values extracted.
     * @param String $yaml     The string to file path for `shipyard.yaml`.
     * @param Object $output   The console output of the Symfony CLI.
     */
    public function __construct($yaml, $output)
    {
        if (array_key_exists('settings', $yaml)) {
            if (array_key_exists('charts_path', $yaml['settings'])) {
                $this->chartsPath = $yaml['settings']['charts_path'];
            }
            if (array_key_exists('stack_path', $yaml['settings'])) {
                $this->stackPath = $yaml['settings']['stack_path'];
            }
            if (array_key_exists('shipyard_tag', $yaml['settings'])) {
                $this->tag = $yaml['settings']['shipyard_tag'];
            }
        }

        if (array_key_exists('stacks', $yaml)) {
            $this->stacks = $yaml['stacks'];
        } else {
            throw new \RuntimeException('No stacks found in shipyard.yaml.');
        }

        $this->output = $output;
    }

    /**
     * Run the stacks of the shipyard.
     */
    public function apply()
    {
        foreach ($this->stacks as $s) {
            $obj = new Stack($s, $this->chartsPath, $this->stackPath, $this->tag, $this->output);
            $obj->run();
        }
    }
}
