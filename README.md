# Castor Docker Runner Library

The `castor-docker-runner` is a PHP library designed to facilitate the execution of commands, particularly within Docker containers. This library is usable with the Castor project.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [RunnerTrait](#runnertrait)
  - [DockerRunnerTrait](#dockerrunnertrait)
- [API Reference](#api-reference)
- [Contribution](#contribution)
- [License](#license)

## Installation

To install the library, use Composer with the following command:

```bash
castor composer require castor/castor-docker-runner
```

## Usage

### RunnerTrait

The `RunnerTrait` provides methods to execute commands. It acts as a builder for running commands. Here's an example of how to use it:

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

### DockerRunnerTrait

The `DockerRunnerTrait` provides methods to execute commands within a Docker container. It adds methods to build the command with Docker options. Here's an example of how to use it:

```php
use Castor\Runner\DockerRunnerTrait;

class SymfonyRunner
{
    use RunnerTrait {
        RunnerTrait::__construct as private __runnerTraitConstruct;
        RunnerTrait::runCommand as private __runCommand;
    }
    use DockerRunnerTrait;

    public function __construct(?Context $castorContext = null)
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

The `DockerRunnerTrait` provides a significant advantage when running scripts in different environments. It allows you to execute commands within a Docker container, and it intelligently determines whether the script is being run from the host or from within a Docker container.

When the script is run from the host, the command is executed in the Docker container using `docker exec`. For example, if you run the script from the host, it will execute the following command:

```bash
docker exec -it --workdir /app --user www-data my-container-name php bin/console cache:clear --env=prod
```

However, if the script is run from within a Docker container, the command is executed directly in the container without the need for `docker exec`. In this case, the following command will be executed:

```bash
php bin/console cache:clear --env=prod
```

This feature simplifies the command execution process and makes your code cleaner and more maintainable. Without this library, you would have to manually check if the script is running inside a Docker container and then construct the command accordingly, as shown in the following example:

```php
$runningInDocker = file_exists('/.dockerenv');

if ($runningInDocker) {
    $command = ['php', 'bin/console', 'cache:clear', '--env=prod'];
} else {
    $command = ['docker', 'exec', '-it', '--workdir', '/app', '--user', 'www-data', 'my-container-name', 'php', 'bin/console', 'cache:clear', '--env=prod'];
}

run($command);
```

With the `DockerRunnerTrait`, this process is abstracted away, allowing you to focus on the logic of your application rather than the specifics of command execution.

## API Reference

- `RunnerTrait`: This trait provides methods to execute commands.
- `DockerRunnerTrait`: This trait provides methods to execute commands within a Docker container.
- `DockerRunner`: This class uses the `RunnerTrait` to execute Docker commands.
- `DockerUtils`: This class provides utility methods for Docker, such as checking if the current environment is inside a Docker container, checking if certain Docker containers are running, checking if a Docker image exists, and checking if a Docker network exists.

## Contribution

If you'd like to contribute to this project, please feel free to fork the repository, make your changes, and submit a pull request.