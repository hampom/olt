<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class SendMessage implements CommandInterface
{
    public function __construct(
        private Connection $conn,
        private int $to,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        $this->conn->writeln();
        $this->conn->write("> ");

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $arg): ?CommandInterface
    {
        if (empty($arg)) {
            $this->conn->writeln("エラーです");
            return null;
        }

        // 送信先を探す
        foreach (Channel::getInstance()->getUserList() as $channel) {
            /** @var Connection $member */
            foreach ($channel as $member) {
                if ($this->to === $member->user->getId()) {
                    $other = $member;
                    break 2;
                }
            }
        }
        if (!isset($other) || !is_object($other)) {
            $this->conn->writeln("エラーです");
            return null;
        }

        // RCAしていないかどうか
        if ($other->user->refuse) {
            $this->conn->writeln("送信できませんでした");
            return null;
        }

        $other->systemMsg("からのメッセージです", $this->conn->user, true);
        $other->writeln($arg, 2);
        $this->conn->systemMsg("送信ＯＫ");

        return null;
    }
}
