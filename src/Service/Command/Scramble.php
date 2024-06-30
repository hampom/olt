<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class Scramble implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private readonly int $scrambleNo
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $no = Channel::getInstance()
            ->findScrambleByCode(
                $this->conn->user->channel,
                $this->scrambleNo
            );

        if ($no) {
            Channel::getInstance()->enterScramble($no, $this->conn->user);
            return null;
        }

        $this->conn->write("タイトルを入力してください : ");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        $result = Channel::getInstance()
            ->createScramble(
                $this->scrambleNo,
                $this->conn->user,
                $arg
            );

        if ($result === false) {
            $this->conn->writeln("エラーです");
        }

        return null;
    }
}
