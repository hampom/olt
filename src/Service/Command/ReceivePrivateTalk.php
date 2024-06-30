<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class ReceivePrivateTalk implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private readonly int $from,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $this->conn->write("入室しますか？ (Y/N): ");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        if (empty($arg)) {
            $this->conn->writeln("エラーです YもしくはN を入力してください。");
            return (new PrivateTalk($this->conn, $this->from))();
        }

        $channels = Channel::getInstance()->getUserList();
        $from = null;
        foreach ($channels as $members) {
            /** @var Connection $member */
            foreach ($members as $member) {
                if ($this->from === $member->user->getId()) {
                    $from = $member;
                    break 2;
                }
            }
        }

        if (!is_object($from)) {
            $this->conn->writeln("エラーです");
            return null;
        }

        $arg = strtolower($arg);
        if (!preg_match("/^[yn]$/", $arg)) {
            $this->conn->writeln("エラーです YもしくはN を入力してください。");
            return (new PrivateTalk($this->conn, $this->from))();
        }

        if ($arg === 'n') {
            $from->systemMsg("プライベートトークが成立しませんでした。");
            return null;
        }

        $this->conn->user->private = $from->user->getId();
        $from->user->private = $this->conn->user->getId();
        return null;
    }
}
