<?php

namespace LinkORB\Shipyard\Model;

class Stack
{
    private string $name;
    private string $chart;
    private string $host;
    private string $values;
    private string $tag;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getChart(): string
    {
        return $this->chart;
    }

    public function setChart(string $chart): void
    {
        $this->chart = $chart;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getValues(): string
    {
        return $this->values;
    }

    public function setValues(string $values): void
    {
        $this->values = $values;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }
}
