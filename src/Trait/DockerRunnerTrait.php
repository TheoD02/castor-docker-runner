<?php

namespace TheoD02\Castor\Runner\Trait;

use Symfony\Component\Process\Process;
use TheoD02\Castor\Runner\Docker\ContainerDefinition;
use function Castor\run;

trait DockerRunnerTrait
{
    private ContainerDefinition $containerDefinition;

    /**
     * This method should return the container definition.
     *
     * That should be defined if allowRunningUsingDocker() returns true.
     */
    abstract public function getContainerDefinition(): ContainerDefinition;

    public function withContainerDefinition(ContainerDefinition $containerDefinition): static
    {
        $this->containerDefinition = $containerDefinition;
        return $this;
    }

    protected function buildDockerCommand(array $baseCommands, ContainerDefinition $containerDefinition): array
    {
        $envs = [];
        foreach ($containerDefinition->envs as $key => $value) {
            $envs[] = '-e';
            $envs[] = "$key=$value";
        }

        $this->commands = [
            'docker',
            'exec',
            '-it',
            ...$envs,
            '--workdir',
            $containerDefinition->workingDirectory,
            $containerDefinition->name,
            'bash',
            '-c',
            '"',
            ...$baseCommands,
            ...$this->commands,
            '"',
        ];

        return $this->commands;
    }

    private function doBuildDockerCommand(): array
    {
        $containerDefinition = $this->containerDefinition ?? $this->getContainerDefinition();
        $baseCommand = is_array($this->getBaseCommand()) ? $this->getBaseCommand() : [$this->getBaseCommand() ?? ''];

        return $this->buildDockerCommand($baseCommand, $containerDefinition);
    }

    protected function runCommandInsideContainer(): Process
    {
        $commands = $this->mergeCommands($this->doBuildDockerCommand());
        $this->commands = [];

        return run($commands, tty: !$this->castorContext?->quiet, context: $this->castorContext);
    }
}