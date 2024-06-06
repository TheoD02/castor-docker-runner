<?php

declare(strict_types=1);

namespace TheoD02\Castor\Runner\Trait;

use Castor\Context;
use Symfony\Component\Process\Process;
use TheoD02\Castor\Runner\Docker\DockerUtils;
use function Castor\run;


/**
 * This trait is used for running commands.
 *
 * It add some useful methods for building commands and running them.
 *
 * It also provides a way to run commands with Docker exec or directly inside a container.
 *
 * @see documentation for how to use it.
 */
trait RunnerTrait
{
    protected array $commands = [];

    public function __construct(
        private readonly ?Context $castorContext = null
    )
    {
    }

    /**
     * Return the base command, e.g. 'composer', null if the command should be run without a base command.
     */
    protected function getBaseCommand(): null|string|array
    {
        return null;
    }

    /**
     * Use that for running anything before the command is executed (e.g. setting environment variables, some checks, etc.).
     */
    protected function preRunCommand(): void
    {
    }

    /**
     * @internal
     */
    protected function mergeCommands(mixed ...$commands): string
    {
        $commands = array_filter($commands);

        $commandsAsArrays = array_map(
            callback: static fn($command) => is_array($command) ? $command : explode(' ', $command),
            array: $commands
        );
        $flattened = array_reduce(
            array: $commandsAsArrays,
            callback: static fn($carry, $item) => [...$carry, ...$item],
            initial: []
        );

        return implode(' ', $flattened);
    }

    /**
     * Add parts of the command.
     *
     * Usage:
     *
     * Imagine you want to run `composer install --no-dev`:
     *
     * getBaseCommand() should return 'composer'
     *
     * $this->add('install', '--no-dev');
     */
    public function add(string ...$commands): static
    {
        $this->commands = [...$this->commands, ...$commands];

        return $this;
    }

    /**
     * Add parts of the command only if the condition is true.
     *
     * Usage:
     *
     * Imagine you want to run `composer install --no-dev` only if the $noDev is true:
     *
     * getBaseCommand() should return 'composer'
     * $noDev = true;
     *
     * $this->add('install');
     * $this->addIf($noDev, '--no-dev');
     *
     * Will run: composer install --no-dev
     *
     * And if you want to add options with values:
     *
     * $this->addIf($noDev, '--no-dev', ['value1', 'value2']);
     *
     * Will run: composer install --no-dev value1 value2
     *
     * And if you want to add options with values and keys:
     *
     * $this->addIf($noDev, null, ['--option1', '--option2']);
     *
     * Will run: composer install --option1 --option2
     */
    public function addIf(mixed $condition, ?string $key = null, string|array|null $value = null): static
    {
        if ($condition !== false && $condition !== null) {
            if ($key === null) {
                $this->commands[] = is_array($value) ? implode(' ', $value) : $value;
            } elseif ($value === null) {
                $this->commands[] = $key;
            } elseif (is_array($value)) {
                $this->commands[] = $key . ' ' . implode(' ' . $key . ' ', $value);
            } else {
                $this->commands[] = $key . ' ' . $value;
            }
        }

        return $this;
    }

    public function debug(bool $inlined = true): void
    {
        $commands = $this->mergeCommands($this->getBaseCommand(), $this->commands);
        if ($inlined) {
            dd($commands);
        }

        dd(explode(' ', $commands));
    }

    public function run(): Process
    {
        $commands = $this->mergeCommands($this->getBaseCommand(), $this->commands);

        $isDockerAware = in_array(DockerRunnerTrait::class, class_uses($this), true);
        $this->preRunCommand();
        if ($isDockerAware && DockerUtils::isRunningInsideContainer() === false) {
            return $this->runCommandInsideContainer();
        }
        $this->commands = [];

        return run($commands, tty: !$this->castorContext?->quiet, context: $this->castorContext);
    }
}
