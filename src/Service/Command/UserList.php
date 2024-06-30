<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class UserList implements CommandInterface
{
    use OutPutUserList;

    public function __construct(
        private readonly Connection $conn,
        private readonly ?int $channel,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $this->conn->systemMsg("現在のご利用者一覧");
        $this->conn->writeln();

        // コマンドの指定がない場合は、現在のチャンネルのユーザー一覧を表示する
        if ($this->channel === null) {
            if ($this->conn->user->channel === null) {
                $this->conn->writeln("エラーです");
                return null;
            }

            $channel = $this->conn->user->channel;
        }

        $channels = Channel::getInstance()->getUserList((int) $channel);
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
