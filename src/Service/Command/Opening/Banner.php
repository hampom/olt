<?php

namespace Olt\Service\Command\Opening;

use Olt\Connection\Connection;
use Olt\Service\Command\CommandInterface;
use Olt\Service\Command\NickName;

final class Banner implements CommandInterface
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

    ★☆★ようこそＯＬＴへ★☆★


EOD
        );

        return (new NickName($this->conn, null))();
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        return null;
    }
}
