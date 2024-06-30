<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;

final class RefuseMessage implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private null $command,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $this->conn->user->refuse = !$this->conn->user->refuse;

        if ($this->conn->user->refuse) {
            $this->conn->systemMsg("メッセージの受信は拒否されます");
        } else {
            $this->conn->systemMsg("メッセージを受信できます");
        }

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
