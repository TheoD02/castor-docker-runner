<?php

namespace TheoD02\Castor\Runner;

use Castor\Context;
use TheoD02\Castor\Runner\Docker\DockerRunner;

function docker(?Context $context = null): DockerRunner
{
    return new DockerRunner($context);
}
