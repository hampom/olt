<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use DateTime;
use Olt\Connection\Connection;

final class Date implements CommandInterface
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
        $this->conn->systemMsg((new DateTime())->format("Y-m-d H:i:s"));

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        return null;
    }
}
