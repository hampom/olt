<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class PrivateTalk implements CommandInterface
{
    public function __construct(
        private Connection $conn,
        private int $to
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        foreach (Channel::getInstance()->getUserList() as $members) {
            /** @var Connection $member */
            foreach ($members as $member) {
                if ($this->to === $member->user->getId()) {
                    $to = $member;
                    break 2;
                }
            }
        }

        if (!is_object($to)) {
            $this->conn->writeln("エラーです");
            return null;
        }

        if (
            !empty($this->conn->user->scramble) ||
            !empty($this->conn->user->private) ||
            !empty($to->user->scramble) ||
            !empty($to->user->private) ||
            !empty($to->mode)
        ) {
            $this->conn->systemMsg("プライベートに誘えませんでした。");
            return null;
        }

        $this->conn->systemMsg(
            sprintf(
                "%s をプライベートトークに誘いました。",
                $to->user->nickName
            )
        );

        $to->systemMsg(
            "さんからプライベートトークのお誘いが届いています。",
            $this->conn->user
        );
        $to->mode = (new ReceivePrivateTalk($to, $this->conn->user->getId()))();

        // トークを誘った側は待機状態に
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
