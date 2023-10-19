<?php

namespace LinkORB\Shipyard;

use DirectoryIterator;
use Symfony\Component\Yaml\Yaml;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

use Spatie\Ssh\Ssh;

class Stack
{

    private $config = NULL;
    private $output = NULL;
    private $charts_path = NULL;
    private $values = NULL;

    public function __construct($config, $charts_path, $output)
    {
        // # Config example values
        // name: my-whoami
        // chart: whoami
        // host: swarm-host-a
        // values: my-whoami/values.yaml

        $this->config = $config;

        $this->charts_path = $charts_path;
        $this->output = $output;

        $this->loadValues();
        $this->loadTemplates();
    }

    public function run()
    {
        $this->output->writeln(sprintf('Stack run `%s(%s)`', $this->config['name'], $this->config['host']));

        // $process = Ssh::create($this->getCurrentUser(), 'localhost')->execute('uname -r');

        // var_dump($process->getOutput());

        // if(!$process->isSuccessful()) {
        //     throw new \RuntimeException($process->getOutput());
        // }

        // $loader = new FilesystemLoader($this->charts_path);
        // $twig = new Environment($loader, [
        //     'cache' => $this->charts_path . DIRECTORY_SEPARATOR . '/compilation_cache',
        // ]);
        // $template = $twig->load('index.html'); 
        // $template->render(['the' => 'variables', 'go' => 'here']);
    }

    private function loadValues()
    {
        if (!array_key_exists('values', $this->config)) {
            throw new \RuntimeException('Stack values not found.');
        }

        $values_file = $this->charts_path . DIRECTORY_SEPARATOR . $this->config['values'];
        if (!file_exists($values_file)) {
            throw new \RuntimeException(sprintf('%s file not found.', $values_file));
        }

        $this->values = Yaml::parseFile($values_file);
    }

    private function loadTemplates()
    {
        $template_path = $this->charts_path . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($template_path)) {
            throw new \RuntimeException(sprintf('%s directory does not found.', $template_path));
        }

        $dir = new DirectoryIterator($template_path);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                var_dump($fileinfo->getFilename());
            }
        }
    }

    private function getCurrentUser()
    {
        return posix_getpwuid(posix_geteuid())['name'];
    }
}
