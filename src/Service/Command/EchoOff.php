<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;

final class EchoOff implements CommandInterface
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
        $this->conn->echo = !$this->conn->echo;
        $statusString = $this->conn->echo ? "開始" : "停止";
        $this->conn->writeln("エコーを" . $statusString);

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
