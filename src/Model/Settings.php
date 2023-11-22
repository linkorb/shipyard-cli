<?php

namespace LinkORB\Shipyard\Model;

class Settings
{
    private string $charts_path;

    //TODO change to enum
    private string $target;

    private string $stack_path;

    private string $shipyard_tag;

    public function getChartsPath(): string
    {
        return $this->charts_path;
    }

    public function setChartsPath(string $charts_path): void
    {
        $this->charts_path = $charts_path;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getStackPath(): string
    {
        return $this->stack_path;
    }

    public function setStackPath(string $stack_path): void
    {
        $this->stack_path = $stack_path;
    }

    public function getShipyardTag(): string
    {
        return $this->shipyard_tag;
    }

    public function setShipyardTag(string $shipyard_tag): void
    {
        $this->shipyard_tag = $shipyard_tag;
    }
}
