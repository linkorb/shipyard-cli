<?php

namespace LinkORB\Shipyard;

class DockerConnectionAdapter implements ConnectionAdapterInterface
{
    private $stackPath = NULL;

    public function __construct($stackPath)
    {
        $this->stackPath = $stackPath;
    }
    public function writeTemplateFiles($files)
    {
        foreach ($files as $file) {
            $path_parts = pathinfo($file);
            $dest_file = $this->stackPath . DIRECTORY_SEPARATOR . str_replace('.tmp', '', $path_parts['filename']);

            $return = shell_exec('mkdir -p ' . $this->stackPath);
            if ($return) {
                unlink($file);
                throw new \RuntimeException($return);
            }
            $return = shell_exec('cp ' . $file . ' ' . $dest_file);                // Copy a template file to the /opt directory.
            unlink($file);
            if ($return) {
                throw new \RuntimeException($return);
            }
        }
    }
    public function dockerComposeUp()
    {
        $return = shell_exec('docker compose up');
        if ($return) {
            throw new \RuntimeException($return);
        }
    }
}
