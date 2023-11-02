<?php

namespace LinkORB\Shipyard;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

            $filesystem = new Filesystem();
            if (!$filesystem->exists($this->stackPath)) {
                try {
                    $filesystem->mkdir(
                        Path::normalize($this->stackPath),
                    );
                } catch (IOExceptionInterface $exception) {
                    $filesystem->remove($file);
                    throw new \RuntimeException("An error occurred while creating your directory at " . $exception->getPath());
                }
            }

            try {
                $filesystem->copy(Path::normalize($file), Path::normalize($dest_file));
            } catch (IOExceptionInterface $exception) {
                $filesystem->remove($file);
                throw new \RuntimeException("File copying error occured at " . $exception->getPath());
            }
            $filesystem->remove($file);
        }
    }
    public function dockerComposeUp()
    {
        $process = new Process(['docker', 'compose', 'up']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
