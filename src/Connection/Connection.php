<?php

declare(strict_types=1);

namespace Olt\Connection;

use DateTimeImmutable;
use Olt\Service\Channel;
use Olt\Service\Command\CommandInterface;
use Olt\Service\Command\Opening\WelcomeWorld;
use Olt\Util\Str;
use React\Socket\ConnectionInterface;

abstract class Connection
{
    /**
     * 出力文字エンコーディング
     */
    public null|false|string $encode = null;

    /**
     * エコーバック設定
     */
    public bool $echo = true;

    /**
     * コマンドモード
     */
    public ?CommandInterface $mode = null;

    /**
     * コマンドリスト
     */
    protected array $commandList = [
        'EchoOff'       => 'ecof',
        'Chat'          => 'ch',
        'Scramble'      => 'sc',
        'PrivateTalk'   => 'pt',
        'Date'          => 'date',
        'Profile'       => 'pf',
        'SetProfile'    => 'spf',
        'UserListAll'   => 'ua',
        'UserList'      => 'u',
        'SendMessage'   => 'ca',
        'RefuseMessage' => 'rca',
        'Close'         => 'e',
    ];

    public function __construct(
        public User $user,
        public ConnectionInterface $conn,
    ) {
    }

    public function connect(): void
    {
        $this->conn->on('data', function (string $message) {
            if ($this->getMessage($message) === false) {
                return;
            }

            if ($this->echo) {
                $this->writeln($message);
            }

            if (isset($this->mode)) {
                $this->mode = $this->mode->run($message);
                return;
            }

            if (preg_match("|^/([a-zA-Z]+)\s*(\d*)|u", $message, $command)) {
                $this->dispatcher($command);
                return;
            }

            Channel::getInstance()->chat($this, $message);
        });

        $this->conn->on('close', function () {
            if ($this->user->channel !== null) {
                $message = "が終了しました。";
                Channel::getInstance()
                    ->out($this->user)
                    ->systemMsg($message, $this);
            }

            printf(
                "[%s] OUT: %s\n",
                (new DateTimeImmutable())->format('c'),
                spl_object_hash($this)
            );
        });
    }

    /**
     * 起動
     */
    public function boot(): void
    {
        $this->mode = (new WelcomeWorld($this, null))();
    }

    protected function dispatcher(array $command): void
    {
        [, $command, $args] = $command;
        if ($className = $this->getCommand($command)) {
            $args = empty($args) ? null : (int) $args;
            $this->mode = (new $className($this, $args))();
            return;
        }

        $this->systemMsg("コマンドはありません");
    }

    protected function getCommand(string $command): false|string
    {
        $commandName = array_search($command, $this->commandList, true);
        if ($commandName === false) {
            return false;
        }

        $className = '\\Olt\\Service\\Command\\' . $commandName;
        if (class_exists($className) === false) {
            return false;
        }

        return $className;
    }

    private function getMessage(string &$message): bool
    {
        // 0xFFで始まるものはスルーする
        if (str_starts_with($message, (string) 0xFF)) {
            return false;
        }

        if (!empty($this->encode)) {
            $message = (string) mb_convert_encoding($message, "UTF-8", $this->encode);
        }

        $message = str_replace(["\x0d\x0a", "\x0a", "\x0d"], "", $message);

        if (empty($message)) {
            return false;
        }

        return true;
    }


    public function close(): void
    {
        $this->conn->close();
    }

    abstract protected function getMessageType(): string;

    abstract public function write(?string ...$message): void;

    public function writeln(string $message = null, int $newLines = 1, int $beforeLines = 0): void
    {
        $this->write(
            str_repeat("\r\n", $beforeLines),
            $message,
            str_repeat("\r\n", $newLines)
        );
    }

    public function systemMsg(string $message, ?User $source = null, bool $withId = false): void
    {
        if ($source !== null) {
            if ($withId) {
                $nickName = $source->nickName . str_repeat(' ', 12 - Str::multiStrlen($source->nickName));

                $message = sprintf("[%2s:%3d:********:%s]", $source->channel, $source->getId(), $nickName) . $message;
            } else {
                $message = sprintf("%s ", $source) . $message;
            }
        }

        $this->writeln("-------- " . $message);
    }

    public function subTitle(): void
    {
        $this->writeln(<<<EOD

    ★☆★ようこそＯＬＴへ★☆★


EOD
        );
    }
}
