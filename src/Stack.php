<?php

namespace LinkORB\Shipyard;

use DirectoryIterator;
use Symfony\Component\Yaml\Yaml;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use JsonSchema\Validator;

use LinkORB\Shipyard\DockerConnectionAdapter;
use LinkORB\Component\Sops\Sops;


class Stack
{

    private $config = NULL;
    private $output = NULL;
    private $chartsPath = NULL;
    private $stackPath = NULL;
    private $values = NULL;
    private $templateFiles = [];
    private $tag = NULL;

    /**
     * Constructor.
     * Saves the parameters for stack.
     * @param Array  $config        An array object received from `shipyard.yaml`
     * @param String $chartsPath    String of the charts path. Default chart path: {cwd}/shipyard/charts
     * @param String $stackPath     String of the stack path.  Default `opt/shipyard/stacks`. Stacks path on remote host or local.
     * @param String $tag           String of tag that optionally only deploys stacks with this tag. Default: empty
     * @param Object $output        Symfony CLI ouput
     */
    public function __construct($config, $chartsPath, $stackPath, $tag, $output)
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
        $this->tag = $tag;

        $this->loadValues();
        $this->loadTemplates();
    }

    /**
     * Run the stack based on the variables loaded from `stacks.yaml`.
     */
    public function run()
    {
        if ($this->tag ) {
            if(!array_key_exists('tag', $this->config) || $this->tag != $this->config['tag']) {
                $this->output->writeln(sprintf('- Skip stack `%s(%s)` because of tag difference.', $this->config['name'], $this->config['host']));
                return;
            }
        }

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
        $sops_used = str_contains($values_file, '.sops.yaml');
        if ($sops_used) {
            $sops = new Sops();
            $sops->decrypt($values_file);
            $values_file = str_replace('.sops', '', $values_file);
        }

        $this->values = Yaml::parseFile($values_file);
        if ($sops_used)
            unlink($values_file);

        // Json-schema validation start
        $json_schema_file = $this->chartsPath . DIRECTORY_SEPARATOR . $this->config['name'] . DIRECTORY_SEPARATOR . 'values.schema.json';
        $yaml_schema_file = $this->chartsPath . DIRECTORY_SEPARATOR . $this->config['name'] . DIRECTORY_SEPARATOR . 'values.schema.yaml';
        $validator = new Validator;
        $schema = NULL;
        if (file_exists($json_schema_file)) {
            $schema = json_decode(file_get_contents($json_schema_file));
        } else if (file_exists($yaml_schema_file)) {
            $schema = Yaml::parseFile($yaml_schema_file);
        }

        if ($schema) {

            $validator->validate($this->values, (object)$schema);

            if ($validator->isValid()) {
                $this->output->writeln("The supplied Values validates against the schema.");
            } else {
                $invalid = "";
                foreach ($validator->getErrors() as $error) {
                    $invalid .= sprintf("[%s] %s\n", $error['property'], $error['message']);
                }
                throw new \RuntimeException(sprintf("JSON does not validate. Violations: %s", $invalid));
            }
        }
        // Json-schema validation end
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
