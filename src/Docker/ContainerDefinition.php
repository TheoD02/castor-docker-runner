<?php

namespace TheoD02\Castor\Runner\Docker;

class ContainerDefinition
{
    public function __construct(
        public string  $composeName,
        public string  $name,
        public string  $workingDirectory,
        public ?string $user = null,
        public array   $envs = [],
    )
    {
    }
}
