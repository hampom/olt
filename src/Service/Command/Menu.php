<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;

final class Menu implements CommandInterface
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
        $this->conn->writeln();
        $this->conn->write("チャンネル番号またはコマンドを入力してください(1-60,Q,UA): ");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        // 小文字に統一する
        $arg = strtolower($arg);
        switch (true) {
            case ($arg === 'q'):
                (new Close($this->conn, null))();
                return null;
            case (preg_match("|^\d+$|", $arg)):
                (new Chat($this->conn, (int) $arg))();
                break;
            case ($arg === 'ua'):
                (new UserListAll($this->conn, null))();
                break;
            default:
                $this->conn->writeln("入力に誤りがあります。");
        }

        if ($this->conn->user->channel === null) {
            return (new self($this->conn, null))();
        }

        return null;
    }
}
