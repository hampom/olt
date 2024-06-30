<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class Chat implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private ?int $channel
    ) {
        if ($this->channel <= 0 || $this->channel > 60) {
            $this->conn->writeln("チャンネル番号に誤りがあります。");
            $this->channel = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        if ($this->channel) {
            Channel::getInstance()
                ->enter($this->channel, $this->conn)
                ->systemMsg("がアクセスしました。", $this->conn);
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
