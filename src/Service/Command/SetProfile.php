<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;

final class SetProfile implements CommandInterface
{
    private string $profile = "";

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
        $this->conn->writeln("プロフィールを入力してください");
        $this->conn->writeln("入力が終わったら「.(ピリオド)」を入力しエンターキーを押してください");
        $this->conn->writeln("");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        if (trim($arg) === '.') {
            $this->conn->writeln("プロフィールの設定を完了しました", 2, 1);

            $this->conn->user->profile = $this->profile;
            return null;
        }

        $this->profile .= $arg . "\r\n";

        return $this;
    }
}
