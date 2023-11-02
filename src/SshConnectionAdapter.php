<?php

namespace LinkORB\Shipyard;

use Spatie\Ssh\Ssh;

class SshConnectionAdapter implements ConnectionAdapterInterface
{
    private $connection = NULL;
    private $stackPath = NULL;

    /**
     * Constructor
     * @param String $stackPath     Receives the stack path.
     * @param String $host          String of a remote host.
     */
    public function __construct($stackPath, $host)
    {
        $this->stackPath = $stackPath;
        $this->connection = Ssh::create($this->getCurrentUser(), $host)->onOutput(function ($type, $line) {
            echo $line;
        });
        $this->connection->disablePasswordAuthentication();
        $this->connection->disableStrictHostKeyChecking();
    }

    /**
     * Copy the template files to the `stackpath` on the remote host via SSH channel.
     * @param Array $files      A files array of the template files.
     */
    public function writeTemplateFiles($files)
    {
        foreach ($files as $file) {
            $path_parts = pathinfo($file);
            $dest_file = $this->stackPath . DIRECTORY_SEPARATOR . str_replace('.tmp', '', $path_parts['filename']);

            $process = $this->connection->execute('mkdir -p ' . $this->stackPath);   // Create parent directory on the remote host.

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

    /**
     * Run the docker compose up
     */
    public function dockerComposeUp()
    {
        $process = $this->connection->execute('docker compose up');

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Docker compose up failed.');
        }
    }

    private function getCurrentUser()
    {
        return posix_getpwuid(posix_geteuid())['name'];
    }
}
