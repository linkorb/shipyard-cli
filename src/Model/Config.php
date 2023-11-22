<?php

namespace LinkORB\Shipyard\Model;

class Config
{
    /**
     * @var Stack[]
     */
    private array $stacks = [];
    private Settings $settings;


    public function addStack(Stack $stack): void
    {
        $this->stacks[] = $stack;
    }

    public function removeStack(Stack $removeStack): void
    {
        foreach ($this->stacks as $k => $stack) {
            if ($stack === $removeStack) {
                unset($this->stacks[$k]);
            }
        }
    }

    public function getStacks(): array
    {
        return $this->stacks;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function setSettings(Settings $settings): void
    {
        $this->settings = $settings;
    }

    public function hasStacks(): bool
    {
        return count($this->stacks) > 0;
    }

    public function getValuesFile(string $name): string
    {
        $values = $this->getStackByName($name)->getValues();
        return $this->settings->getChartsPath() . DIRECTORY_SEPARATOR . $values;
    }

    public function getStackByName(string $name): ?Stack
    {
        foreach ($this->stacks as $stack) {
            if ($stack->getName() === $name) {
                return $stack;
            }
        }
        return null;
    }
}
