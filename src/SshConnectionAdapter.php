<?php

namespace LinkORB\Shipyard;

use Spatie\Ssh\Ssh;

class SshConnectionAdapter implements ConnectionAdapterInterface
{
    private $connection = NULL;
    private $stackPath = NULL;

    public function __construct($stackPath, $host)
    {
        $this->stackPath = $stackPath;
        $this->connection = Ssh::create($this->getCurrentUser(), $host)->onOutput(function ($type, $line) {
            echo $line;
        });
        $this->connection->disablePasswordAuthentication();
    }

    public function writeTemplateFiles($files)
    {
        foreach ($files as $file) {
            $path_parts = pathinfo($file);
            $dest_file = $this->stackPath . DIRECTORY_SEPARATOR . str_replace('.tmp', '', $path_parts['filename']);
            
            $process = $this->connection->execute('mkdir -p '. $this->stackPath);   // Create parent directory on the remote host.

            if (!$process->isSuccessful()) {
                unlink($file);
                throw new \RuntimeException($process->getOutput());
            }

            $process = $this->connection->upload($file, $dest_file);                // Upload a template file to the remote host.
            unlink($file);
            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getOutput());
            }
        }
    }
    public function dockerComposeUp()
    {
        $process = $this->connection->execute('docker compose up');

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getOutput());
        }
    }

    private function getCurrentUser()
    {
        return posix_getpwuid(posix_geteuid())['name'];
    }
}
