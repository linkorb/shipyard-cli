<?php
namespace LinkORB\Shipyard;

interface ConnectionAdapterInterface
{
    public function writeTemplateFiles($files);
    public function dockerComposeUp();
}
