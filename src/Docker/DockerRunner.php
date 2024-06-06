<?php

namespace TheoD02\Castor\Runner\Docker;

use Castor\Context;
use TheoD02\Castor\Runner\Trait\RunnerTrait;

class DockerRunner
{
    use RunnerTrait {
        RunnerTrait::__construct as private __runnerTraitConstruct;
    }

    public function __construct(?Context $context = null)
    {
        $this->__runnerTraitConstruct($context);
    }

    protected function getBaseCommand(): string
    {
        return 'docker';
    }
}