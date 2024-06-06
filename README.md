# Castor Docker Runner Library Documentation

## Overview

The `castor-docker-runner` is a PHP library designed to facilitate the execution of commands, particularly within Docker
containers or not.

It is usable with Castor project and is primarily composed of two traits: `RunnerTrait` and `DockerRunnerTrait`, which
are used in the `DockerRunner` class.

## Installation

To install the library, you can use Composer with castor:

```bash
castor composer require castor/castor-docker-runner
```

## Usage

### RunnerTrait

The `RunnerTrait` is a trait that provides some methods to execute commands. Let's take this one like a builder for
running commands.

This one didn't add really much to the `run` function of the one provided by `castor` but it adds some useful methods to
build the command.

#### Example

Make a runner for Composer:

```php
use Castor\Runner\RunnerTrait;

class MyRunner
{
    use RunnerTrait;
    
    public function getBaseCommand()
    {
        return 'composer'
    }
}

$runner = new MyRunner();
$runner->add('install')->run(); // Run the command "composer install"
```

At this point, what the hell ? Why not just use `castor` ?

But you can do more with this trait, like adding options, arguments with conditions, etc.

#### Example

```php
$runner
    ->add('require', 'vendor/package')
    ->addIf($devMode, '--dev') // Add the option "--dev" if $devMode is true
    ->run(); // If $devMode is true, run the command "composer require vendor/package --dev" else "composer require vendor/package"
```

But not really interesting, right ? Let's see the `DockerRunnerTrait`.

### DockerRunnerTrait

The `DockerRunnerTrait` is a trait that provides some methods to execute commands within a Docker container. Based on
the `RunnerTrait`, it adds some methods to build the command with Docker options.

#### Example

Make a runner for Symfony console:

```php
use Castor\Runner\DockerRunnerTrait;

class SymfonyRunner
{
    use RunnerTrait {
        RunnerTrait::__construct as private __runnerTraitConstruct;
        RunnerTrait::runCommand as private __runCommand;
    }
    use DockerRunnerTrait;

    public function __construct(
        private readonly string $appId = 'tenants',
        private readonly string $tenant = 'EFF',
        ?Context                $castorContext = null,
    )
    {
        $this->__runnerTraitConstruct($castorContext);
    }

    protected function getBaseCommand(): array
    {
        return ['php', 'bin/console'];
    }

    public function getContainerDefinition(): ContainerDefinition
    {
        return new ContainerDefinition(
            composeName: 'my-container-compose-name',
            name: 'my-container-name',
            workingDirectory: '/app',
            user: 'www-data',
            envs: [],
        );
    }
}

$sfRunner = new SymfonyRunner();

$sfRunner
    ->add('cache:clear')
    ->add('--env', 'prod')
    ->run(); // Run the command "docker-compose exec -T my-container-compose-name php bin/console cache:clear --env=prod"
```

Ok, now it's more interesting. You can run commands within a Docker container with this trait.

But the big advantage is if you run castor script from Host, it will run the command in docker container by
using `docker exec`.
If you run castor script from Docker container, it will run the command directly in the container without `docker exec`.

In the current case if you run the script from Host, it will run the command:

```bash
docker exec -it --workdir /app --user www-data my-container-name php bin/console cache:clear --env=prod
```

And if you run the script from Docker container, it will run the command:

```bash
php bin/console cache:clear --env=prod
```

By default if you don't use this library you will have to do something like this:

```php
$runningInDocker = file_exists('/.dockerenv');

if ($runningInDocker) {
    $command = ['php', 'bin/console', 'cache:clear', '--env=prod'];
} else {
    $command = ['docker', 'exec', '-it', '--workdir', '/app', '--user', 'www-data', 'my-container-name', 'php', 'bin/console', 'cache:clear', '--env=prod'];
}

run($command);
```
