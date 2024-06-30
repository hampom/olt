<?php

declare(strict_types=1);

namespace Olt\Connection;

use Olt\Util\Str;

final class User
{
    /**
     * 受信メッセージ拒否設定
     */
    public bool $refuse = false;

    /**
     * スクランブルコード(4桁)
     */
    public ?int $scramble = null;

    /**
     * プライベートトーク(相手のID)
     */
    public ?int $private = null;

    /**
     * ニックネーム
     */
    public string $nickName = "";

    /**
     * 会話に参加しているチャンネルナンバー
     */
    public ?int $channel = null;

    /**
     * プロフィール
     */
    public string $profile = "";

    public function __construct(
        protected int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function statusStdout(): void
    {
        printf("nickName: [%s]" . PHP_EOL, $this->nickName);
        printf("      id: %d" . PHP_EOL, $this->getId());
        printf(" channel: %d" . PHP_EOL, $this->channel);
        printf("Scramble: %d" . PHP_EOL, $this->scramble);
        printf(" private: %d" . PHP_EOL, $this->private);
        echo PHP_EOL;
    }

    public function __toString(): string
    {
        // 名前の後ろに空白を埋める(sprintf()がマルチバイト非対応の為
        $nickName = $this->nickName . str_repeat(' ', 12 - Str::multiStrlen($this->nickName));

        return sprintf("[%2s:%3d:%s]", $this->channel, $this->getId(), $nickName);
    }
}
