<?php

namespace LinkORB\Shipyard;

use DirectoryIterator;
use Symfony\Component\Yaml\Yaml;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

use LinkORB\Shipyard\DockerConnectionAdapter;


class Stack
{

    private $config = NULL;
    private $output = NULL;
    private $chartsPath = NULL;
    private $stackPath = NULL;
    private $values = NULL;
    private $templateFiles = [];

    public function __construct($config, $chartsPath, $stackPath, $output)
    {
        // # Config example values
        // name: my-whoami
        // chart: whoami
        // host: swarm-host-a
        // values: my-whoami/values.yaml

        $this->config = $config;

        $this->chartsPath = $chartsPath;
        $this->stackPath = $stackPath . DIRECTORY_SEPARATOR . $this->config['name'];
        $this->output = $output;

        $this->loadValues();
        $this->loadTemplates();
    }

    public function run()
    {
        $this->output->writeln(sprintf('- Stack run `%s(%s)`', $this->config['name'], $this->config['host']));

        if (array_key_exists('host', $this->config)) {
            $host = $this->config['host'];
            if ($host == 'localhost') {
                $adapter = new DockerConnectionAdapter($this->stackPath);

                $this->output->writeln('copying template files...');
                $adapter->writeTemplateFiles($this->templateFiles);

                $this->output->writeln('docker compose up...');
                $adapter->dockerComposeUp();
            } else {
                $adapter = new SshConnectionAdapter($this->stackPath, $host);

                $this->output->writeln('copying template files...');
                $adapter->writeTemplateFiles($this->templateFiles);

                $this->output->writeln('docker compose up...');
                $adapter->dockerComposeUp();
            }
        } else {
            throw new \RuntimeException('`host` is missing in stack configuration.');
        }
    }

    private function loadValues()
    {
        if (!array_key_exists('values', $this->config)) {
            throw new \RuntimeException('Stack values not found.');
        }

        $values_file = $this->chartsPath . DIRECTORY_SEPARATOR . $this->config['values'];
        if (!file_exists($values_file)) {
            throw new \RuntimeException(sprintf('%s file not found.', $values_file));
        }

        $this->values = Yaml::parseFile($values_file);
    }

    private function loadTemplates()
    {
        $template_path = $this->chartsPath . DIRECTORY_SEPARATOR . $this->config['name'] . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($template_path)) {
            throw new \RuntimeException(sprintf('%s directory does not found.', $template_path));
        }
        // scan and twig template files
        $dir = new DirectoryIterator($template_path);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $this->templateFiles[] = $this->twigTemplateFile($fileinfo->getFilename(), $fileinfo->getPath());
            }
        }

        if (count($this->templateFiles) == 0) {
            throw new \RuntimeException(sprintf('No template files found in %s.', $template_path));
        }
    }

    private function twigTemplateFile($filename, $dirPath)
    {
        $loader = new FilesystemLoader($dirPath);
        $twig = new Environment($loader, [
            'cache' => $this->chartsPath . DIRECTORY_SEPARATOR . '/compilation_cache',
        ]);
        $template = $twig->load($filename);
        $rendered = $template->render(['Values' => $this->values]);
        $tmpPath = $dirPath . DIRECTORY_SEPARATOR . $filename . '.tmp';
        file_put_contents($tmpPath, $rendered);
        return $tmpPath;
    }
}
