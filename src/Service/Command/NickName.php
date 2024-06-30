<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use InvalidArgumentException;
use Olt\Connection\Connection;

final class NickName implements CommandInterface
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
        $this->conn->write("ニックネームを入力してください : ");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        try {
            $this->conn->user->nickName = $arg;

            // まだチャンネルに参加していないのならメニューを表示
            if (empty($this->app->channel)) {
                return (new Menu($this->conn, null))();
            }

            return null;
        } catch (InvalidArgumentException $e) {
            print $e->getMessage();
            $this->conn->writeln("エラーです");

            return (new self($this->conn, null))();
        }
    }
}
