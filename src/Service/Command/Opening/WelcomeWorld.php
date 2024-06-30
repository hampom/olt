<?php

namespace Olt\Service\Command\Opening;

use Olt\Connection\Connection;
use Olt\Service\Command\CommandInterface;

final class WelcomeWorld implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private readonly null $command
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $this->conn->writeln(<<<EOD

/////////////// WELCOME TO ONLINE TALK ///////////////

EOD
        );

        return (new EncodeSetting($this->conn, null))();
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        return null;
    }
}
