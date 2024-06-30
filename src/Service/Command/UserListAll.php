<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class UserListAll implements CommandInterface
{
    use OutPutUserList;

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
        $this->conn->systemMsg("現在のご利用者一覧");
        $this->conn->writeln();

        $channels = Channel::getInstance()->getUserList();
        $this->outPutUserList($this->conn, $channels);
        $this->conn->writeln();

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
