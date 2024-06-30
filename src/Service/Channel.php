<?php

declare(strict_types=1);

namespace Olt\Service;

use Olt\Connection\Connection;
use Olt\Connection\User;

class Channel
{
    /* 通知相手: チャンネルメンバー全員(SC,PTを含む)
     */
    const NOTICE_ALL = 1;

    /* 通知相手: SCメンバーのみ
     */
    const NOTICE_SC = 2;

    /* 通知相手: PTメンバーのみ
     */
    const NOTICE_PT = 3;

    /* 通知相手: チャンネルメンバーのみ
     */
    const NOTICE_CH = 4;

    private static Channel $instance;

    /**
     * @var array{int: Connection[]}[]
     */
    private array $no = [];

    /**
     * @var array{int: array{code: int, title: string}[]}
     */
    private array $sc = [];

    public static function getInstance(): Channel
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 指定チャンネル内の全てのスクランブルトークのタイトルを取得
     *
     * @return array<int, string>
     */
    public function getScrambleTitle($channel): array
    {
        $scTitles = [];
        if (empty($this->sc[$channel])) {
            return $scTitles;
        }

        foreach ($this->sc[$channel] as $no => $scramble) {
            $scTitles[$no] = $scramble['title'];
        }

        return $scTitles;
    }

    /**
     * スクランブルトーク作成
     */
    public function createScramble(int $scCode, User $user, $title = null): bool
    {
        if (empty($user->channel)) {
            return false;
        }

        $no = 0;
        do {
            $no++;
        } while (!empty($this->sc[$user->channel][$no]));

        $this->sc[$user->channel][$no] = [
            'code'  => $scCode,
            'title' => $title
        ];

        return $this->enterScramble($no, $user);
    }

    /**
     * スクランブルトークに参加
     */
    public function enterScramble(int $no, User $user): bool
    {
        if (!empty($user->scramble)) {
            return false;
        }

        $user->scramble = $no;

        /** @var Connection $other */
        foreach ($this->no[$user->channel] as $other) {
            $other->user->statusStdout();
            if ($other->user->scramble === $no) {
                $other->systemMsg("がスクランブルモードに加わりました。", $user);
            }
        }
        return true;
    }

    /**
     * スクランブルトーク検索
     */
    public function findScrambleByCode(int $channel, int $scCode): int|null
    {
        if (empty($this->sc)) {
            return null;
        }

        foreach ($this->sc[$channel] as $no => $scramble) {
            if ($scramble['code'] === $scCode) {
                return $no;
            }
        }

        return null;
    }

    /**
     * チャンネルに参加
     */
    public function enter(int $channel, Connection $conn): static
    {
        if (!is_null($conn->user->channel)) {
            $message = "がチャンネルを移動しました。";
            $this->systemMsg($message, $conn);
            $this->out($conn->user);
        } else {
            $conn->writeln();
            $conn->systemMsg("どうぞ、ご利用を始めて下さい。");
        }

        $this->no[$channel][] = $conn;
        $conn->user->channel = $channel;

        return $this;
    }

    /**
     * チャンネルから退出
     */
    public function out(User $user): static
    {
        if (empty($this->no)) {
            return $this;
        }

        // スクランプル・プライベートトークを強制解除
        $ptUserId = $user->private;
        $user->scramble = $user->private = null;

        $scCount = [];
        /** @var Connection $other */
        foreach ($this->no[$user->channel] as $key => $other) {
            // スクランブル参加人数計上
            if (!isset($scCount[$other->user->scramble])) {
                $scCount[$other->user->scramble] = 0;
            }
            ++$scCount[$other->user->scramble];

            if ($other->user->getId() === $user->getId()) {
                if (count($this->no[$user->channel]) < 2) {
                    unset($this->no[$user->channel]);
                } else {
                    unset($this->no[$user->channel][$key]);
                }

                // 退出する本人はスクランブル人数に加算しない
                --$scCount[$other->user->scramble];
            }
        }

        // ゼロ人のスクランブルを解散する
        foreach ($scCount as $no => $count) {
            if ($count === 0) {
                unset($this->sc[$no]);
            }
        }

        // 1人になったプライベートトークを強制解除
        if ($ptUserId !== null) {
            foreach ($this->getUserList() as $members) {
                /** @var Connection $member */
                foreach ($members as $member) {
                    if ($ptUserId === $member->user->getId()) {
                        $member->user->private = null;
                        break 2;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * チャンネル(スクランブル・プライベートを含む)で発言
     */
    public function chat(Connection $source, string $message): void
    {
        if (!$source->user->channel) {
            return;
        }

        $source->writeln(sprintf("%s %s", $source->user, $message));

        // 同じチャンネルに居るユーザーに送信
        /** @var Connection $to */
        foreach ($this->no[$source->user->channel] as $to) {
            if ($to->user->scramble !== $source->user->scramble) {
                continue;
            }

            if (!empty($source->user->private) && $source->user->private !== $to->user->getId()) {
                continue;
            }

            if ($source->user->getId() === $to->user->getId()) {
                continue;
            }

            $to->writeln(sprintf("%s %s", $source->user, $message));
        }
    }

    /**
     * 指定したチャンネルまたは全チャンネルのユーザー一覧を取得
     *
     * @return array{int: User[]}
     */
    public function getUserList(?int $channel = null): array
    {
        if ($channel !== null) {
            return [
                $channel => !empty($this->no[$channel])
                    ? $this->no[$channel]
                    : []
            ];
        }

        return !empty($this->no)
            ? $this->no
            : [$channel => []];
    }

    /**
     * システムメッセージとしてチャンネル参加ユーザーに通知
     */
    public function systemMsg(string $message, Connection $source): void
    {
        if (empty($this->no[$source->user->channel])) {
            return;
        }

        $notice = null;

        // チャンネルメンバーのアクション： チャンネルメンバーのみ
        if (empty($source->user->scramble) && empty($source->user->private)) {
            $notice = self::NOTICE_CH;
        }

        // SCメンバーのアクション： 同室のSCメンバーおよびチャンネルメンバー
        if (!empty($source->user->scramble)) {
            $notice = self::NOTICE_SC;
        }

        // PTメンバーのアクション： 同室のPTメンバーおよびチャンネルメンバー
        if (!empty($source->user->private)) {
            $notice = self::NOTICE_CH;
        }

        if (is_null($notice)) {
            return;
        }

        /** @var Connection $other */
        foreach ($this->no[$source->user->channel] as $other) {
            // チャンネルメンバーのアクションはチャンネルメンバーにのみ通知する
            if ($notice === self::NOTICE_CH) {
                if (!empty($other->user->scramble) || !empty($other->user->private)) {
                    continue;
                }
            }

            if (
                ($notice === self::NOTICE_SC) &&
                !empty($other->user->scramble) && $other->user->scramble !== $source->user->scramble
            ) {
                continue;
            }

            // 本人には通知しない
            if ($other === $source) {
                continue;
            }

            $other->systemMsg($message, $source->user);
        }
    }
}
