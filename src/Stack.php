<?php

namespace LinkORB\Shipyard;

use DirectoryIterator;
use JsonSchema\Validator;
use LinkORB\Component\Sops\Sops;
use LinkORB\Shipyard\Model\Config;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


class Stack
{
    private array $templateFiles = [];
    private array $values;
    private ?Model\Stack $model;

    /**
     * Saves the parameters for stack.
     */
    public function __construct(private readonly Config $config, string $name, private readonly OutputInterface $output)
    {
        $this->model = $this->config->getStackByName($name);

        $this->loadValues();
        $this->loadTemplates();
    }

    private function loadValues()
    {
        if (!$this->model->getValues()) {
            throw new \RuntimeException('Stack values not found.');
        }

        $values_file = $this->config->getValuesFile($this->model->getName());
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
        $path = $this->getSettings()->getChartsPath() . DIRECTORY_SEPARATOR
            . $this->model->getName() . DIRECTORY_SEPARATOR;
        $json_schema_file = $path . 'values.schema.json';
        $yaml_schema_file = $path . 'values.schema.yaml';
        $validator = new Validator;
        $schema = null;
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

    protected function getSettings(): Model\Settings
    {
        return $this->config->getSettings();
    }

    private function loadTemplates()
    {
        $template_path = $this->getSettings()->getChartsPath() . DIRECTORY_SEPARATOR
            . $this->model->getName() . DIRECTORY_SEPARATOR . 'templates';
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
            'cache' => $this->getSettings()->getChartsPath() . DIRECTORY_SEPARATOR
                . '/compilation_cache',
        ]);
        $template = $twig->load($filename);
        $rendered = $template->render(['Values' => $this->values]);
        $tmpPath = $dirPath . DIRECTORY_SEPARATOR . $filename . '.tmp';
        file_put_contents($tmpPath, $rendered);
        return $tmpPath;
    }

    /**
     * Run the stack based on the variables loaded from `stacks.yaml`.
     */
    public function run()
    {
        if ($this->model->getTag() != $this->getSettings()->getShipyardTag()) {
            $this->output->writeln(sprintf(
                '- Skip stack `%s(%s)` because of tag difference.',
                $this->model->getName(),
                $this->model->getHost(),
            ));
            return;
        }

        $this->output->writeln(sprintf(
            '- Stack run `%s(%s)`',
            $this->model->getName(),
            $this->model->getHost(),
        ));

        if (!$this->model->getHost()) {
            throw new \RuntimeException('`host` is missing in stack configuration.');
        }

        if ($this->model->getHost() == 'localhost') {
            $adapter = new DockerConnectionAdapter($this->getStackPath());

            $this->output->writeln('copying template files...');
            $adapter->writeTemplateFiles($this->templateFiles);

            $this->output->writeln('docker compose up...');
            $adapter->dockerComposeUp();
        } else {
            $adapter = new SshConnectionAdapter($this->getStackPath(), $this->model->getHost());

            $this->output->writeln('copying template files...');
            $adapter->writeTemplateFiles($this->templateFiles);

            $this->output->writeln('docker compose up...');
            $adapter->dockerComposeUp();
        }
    }

    protected function getStackPath(): string
    {
        return $this->getSettings()->getStackPath() . DIRECTORY_SEPARATOR . $this->model->getName();
    }
}
