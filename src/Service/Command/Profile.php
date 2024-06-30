<?php

declare(strict_types=1);

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

final class Profile implements CommandInterface
{
    public function __construct(
        private readonly Connection $conn,
        private readonly ?int $userId,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): ?CommandInterface
    {
        if (empty($this->userId)) {
            $this->conn->writeln("指定のユーザーはいません", 2, 1);
            return null;
        }

        $other = null;
        foreach (Channel::getInstance()->getUserList() as $members) {
            /** @var Connection $member */
            foreach ($members as $member) {
                if ($this->userId === $member->user->getId()) {
                    $other = $member;
                    break 2;
                }
            }
        }
        if (!is_object($other)) {
            $this->conn->writeln("指定のユーザーはいません", 2, 1);
            return null;
        }

        // 自身のプロフィールが未設定の場合は見ることができない
        $profile = $this->conn->user->profile;
        if ($profile) {
            $this->conn->writeln($other->user->profile, 2, 1);
        } else {
            $this->conn->systemMsg("プロフィールが設定されていません");
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
