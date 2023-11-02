<?php
namespace LinkORB\Shipyard;

interface ConnectionAdapterInterface
{
    /**
     * Copy the template files to the `stackpath`
     * @param Array $files      A files array of the template files.
     */
    public function writeTemplateFiles($files);
    /**
     * Run the docker compose up
     */
    public function dockerComposeUp();
}
