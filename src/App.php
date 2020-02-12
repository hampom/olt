<?php declare(strict_types=1);

namespace olt;

use DateTime;
use InvalidArgumentException;
use React\Socket\ConnectionInterface;

class App
{
    /** @var null|ConnectionInterface */
    private $conn;
    private $id;
    private $status = null;
    private $encode = false;
    private $echo = true;

    public  $refuse = false;
    public  $scrunble = null;
    public  $private = null;
    public  $nickName;
    public  $channel;

    private $statuses = [
        'onMenu'          => null,
        'onEncodeSetting' => null,
        'onNickName'      => null,
        'onEchoOff'       => 'ecof',
        'onChat'          => 'ch',
        'onScrunble'      => 'sc',
        'onPrivateTalk'   => 'pt',
        'onDate'          => 'date',
        'onUserListAll'   => 'ua',
        'onUserList'      => 'u',
        'onSendMessage'   => 'ca',
        'onRefuseMessage' => 'rca',
        'onClose'         => 'e',
    ];

    /**
     * @param integer $id
     * @param ConnectionInterface $conn
     */
    public function connect($id, ConnectionInterface $conn): void
    {
        $this->conn = $conn;
        $this->id = $id;

        // opening
        $this->welcomeWorld();
        $this->setStatus('onEncodeSetting');

        $this->conn->on('data', function($message) {
            if (!empty($this->encode)) {
                $message = mb_convert_encoding(
                    $message,
                    "UTF-8",
                    $this->encode
                );
            }

            $message = str_replace(["\x0d\x0a", "\x0a", "\x0d"], "", $message);
            if (is_null($message)) {
                return;
            }
            if (empty($message)) {
                return;
            }

            if (!is_null($status = $this->getStatus())) {
                call_user_func([$this, $status], $message);
                return;
            }

            if (preg_match("|^/([a-zA-Z]+)\s*([0-9]*)|u", $message, $command)) {
                $this->dispatcher($command);
                return;
            }

            if (!is_null($this->channel)) {
                Channel::getInstance()->chat($this, $message);
            }

            return;
        });

        $this->conn->on('close', function() {
            if (!empty($this->channel)) {
                $message = "が終了しました。";
                Channel::getInstance()->out($this)
                                      ->systemMsg($message, $this);
            }

            printf(
                "[%s] OUT: %s\n",
                (new \DateTimeImmutable())->format('c'),
                spl_object_hash($this),
            );
        });
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function onMenu($command = null): void
    {
        if (!empty($command)) {
            $this->clearStatus();

            switch (true) {
                case (preg_match("|^[0-9]+$|", $command)) :
                    if ($this->onChat($command)) {
                        return;
                    }
                    break;
                case ($command == 'e') :
                    $this->onClose();
                    break;
                case ($command == 'ua') :
                    $this->onUserListAll();
                    break;
                default :
                    $this->writeln("入力に誤りがあります。");
            }
        }

        $this->writeln();
        $this->prompt("チャンネル番号またはコマンドを入力してください(1-60,E,UA): ", __METHOD__);
    }

    private function dispatcher(array $command): void
    {
        list(, $command, $args) = $command;
        if ($status = $this->getStatusByCommand(strtolower($command))) {
            call_user_func([$this, $status], $args);
            return;
        }

        $this->systemMsg("コマンドはありません");
    }

    private function getStatusByCommand($command)
    {
        return array_search($command, $this->statuses, true);
    }

    private function checkStatuses($status)
    {
        $tmp = explode(":", $status);
        $status = end($tmp);
        return !in_array(
            $status,
            array_keys($this->statuses),
            true
        );
    }

    private function setStatus($status)
    {
        if (!is_array($status)) {
            $status = [0 => $status];
        }

        $newStatus = $status[0];
        if ($this->checkStatuses($newStatus)) {
            throw new InvalidArgumentException;
        }

        $this->status = $status;
    }

    private function getStatus()
    {
        return $this->status[0];
    }

    private function clearStatus()
    {
        $this->status = null;
    }

    private function onUserListAll(): bool
    {
        $this->systemMsg("現在のご利用者一覧");
        $this->writeln();

        $channels = Channel::getInstance()->getUserList();
        $this->outPutUserList($channels);
        $this->writeln();

        return true;
    }

    private function onUserList($channel = null): bool
    {
        $this->systemMsg("現在のご利用者一覧");
        $this->writeln();

        if (empty($channel)) {
            if (empty($this->channel)) {
                $this->writeln("エラーです");
                return false;
            }
            $channel = $this->channel;
        }

        $channels = Channel::getInstance()->getUserList($channel);
        $this->outPutUserList($channels);
        $this->writeln();

        return true;
    }

    private function onDate(): void
    {
        $date = new DateTime;
        $this->systemMsg($date->format("Y-m-d H:i:s"));
    }

    private function onEchoOff(): void
    {
        $this->onEchoSetting(false);
    }

    private function onEchoSetting($arg): void
    {
        $this->echo = $arg;
        $message = "エコーを";
        $message .= ($arg)? "開始" : "停止";
        $this->writeln($message);
    }

    private function outPutUserList($channels): void
    {

        if ($channels === false) {
            $this->systemMsg("利用者がいません。");
            return;
        }

        foreach($channels as $no => $channel) {
            $this->writeln(
                sprintf(
                    "チャンネル %2d (%2d)",
                    $no,
                    count($channel)
                )
            );
            $this->writeln();

            $i = 0;
            foreach ($channel as $member)
            {
                // 名前の後ろに空白を埋める(sprintf()がマルチバイト非対応の為
                $nickName = $member->nickName .
                            str_repeat(' ', 12 - $this->multi_strlen($member->nickName));
                $list = sprintf(
                    "[%2s:%3d:%s]【漢字】",
                    !empty($member->scrunble)
                        ? 'S' . $member->scrunble
                        : $member->channel,
                    $member->getId(),
                    $nickName
                );

                if (++$i / 2) {
                    $outCmd = "writeln";
                } else {
                    $outCmd = "write";
                    // indent space
                    $list .= '  ';
                }

                $this->{$outCmd}($list);
            }

            if (count($channel) % 2) {
                $this->writeln();
            }

            $scTitles = Channel::getInstance()->getScrunbleTitle($no);

            if (!empty($scTitles)) {
                $this->writeln();
                foreach ($scTitles as $scNo => $title) {
                    $this->writeln(
                        sprintf(
                            "SC(%d) %s",
                            $scNo,
                            $title
                        )
                    );
                }
            }
        }
    }

    private function onChat($no = null): bool
    {
        if (!is_numeric($no) ||
            $this->channel == $no ||
            !($no > 0 && $no <= 60)) {
            $this->writeln("チャンネル番号に誤りがあります。");
            return false;
        }

        $message = "がアクセスしました。";
        Channel::getInstance()->enter($no, $this)
                              ->systemMsg($message, $this);

        return true;
    }

    private function onScrunble($args = null): void
    {
        if (__METHOD__ == $this->getStatus()) {
            $scCode = $this->status['scCode'];
            $this->clearStatus();

            if (Channel::getInstance()->createScrunble($scCode, $this, $args)) {
                return;
            }

            $this->writeln("エラーです");
            return;
        }

        if ($no = Channel::getInstance()->findScrunbleByCode($this->channel, $args)) {
            Channel::getInstance()->enterScrunble($no, $this);
            return;
        }

        $status = [0 => __METHOD__, 'scCode' => $args];
        $this->prompt("タイトルを入力してください : ", $status);
    }

    private function onNickName($message = null): void
    {
        if (__METHOD__ == $this->getStatus()) {
            $this->clearStatus();

            if (!empty($message) && $this->multi_strlen($message) <= 12) {
                $this->nickName = $message;

                if (empty($this->channel)) {
                    $this->onMenu();
                }
                return;
            }

            $this->writeln("エラーです");
        }

        $this->prompt("ニックネームを入力してください : ", __METHOD__);
    }

    private function onRefuseMessage(): void
    {
        $this->refuse = !$this->refuse;

        if ($this->refuse) {
            $this->systemMsg("メッセージの受信は拒否されます");
        } else {
            $this->systemMsg("メッセージを受信できます");
        }
    }

    private function onPrivateTalk($args = null): void
    {
        if (__METHOD__  == $this->getStatus()) {
            $from = $this->status['from'];

            if (empty($args)) {
                $this->writeln("エラーです YもしくはN を入力してください。");
                return;
            }

            $members = Channel::getInstance()->getUserList($this->channel);
            foreach ($members as $member) {
                if ($from == $member->getId()) {
                    $from = $member;
                    break;
                }
            }

            if (!is_object($from)) {
                $this->writeln("エラーです");
                return;
            }

            $args = strtolower($args);
            if (!preg_match("/^[yn]$/", $args)) {
                $this->writeln("エラーです YもしくはN を入力してください。");
                return;
            }

            $this->clearStatus();

            if ($args == 'n') {
                $from->systemMsg("プライベートトークが成立しませんでした。");
                return;
            }

            $this->private = $from->getId();
            $from->private = $this->getId();
            return;
        }

        $to = null;
        foreach (Channel::getInstance()->getUserList() as $no => $members) {
            foreach ($members as $member) {
                if ($args == $member->getId()) {
                    $to = $member;
                    break 2;
                }
            }
        }

        if (!is_object($to)) {
            $this->writeln("エラーです");
            return;
        }

        if (!empty($this->scrunble) ||
            !empty($this->private) ||
            !empty($to->scrunble) ||
            !empty($to->private)) {
            $this->systemMsg("プライベートに誘えませんでした。");
            return;
        }

        $this->systemMsg(sprintf("%s をプライベートトークに誘いました。", $to->nickName));

        $status = [0 => __METHOD__, 'from' => $this->id];
        $to->systemMsg("さんからプライベートトークのお誘いが届いています。", $this);
        $to->prompt("入室しますか？ (Y/N): ", $status);
    }

    private function onSendMessage($args = null): void
    {
        if (__METHOD__  == $this->getStatus()) {
            $to = $this->status['to'];
            $this->clearStatus();

            if (empty($args)) {
                $this->writeln("エラーです");
                return;
            }

            foreach (Channel::getInstance()->getUserList() as $no => $members) {
                foreach ($members as $member) {
                    if ($to == $member->getId()) {
                        $to = $member;
                        break 2;
                    }
                }
            }
            if (empty($to) || !is_object($to)) {
                $this->writeln("エラーです");
                return;
            }

            if ($to->refuse) {
                $this->writeln("送信できませんでした");
                return;
            }

            $to->systemMsg("からのメッセージです", $this, true);
            $to->writeln($args);
            $this->systemMsg("送信ＯＫ");
            return;
        }

        $status = [0 => __METHOD__, 'to' => $args];
        $this->writeln();
        $this->prompt(">", $status);
    }

    private function onClose(): void
    {
        $this->conn->close();
    }

    public function write($message): void
    {
        if (!empty($this->encode)) {
            $message = mb_convert_encoding(
                $message,
                $this->encode,
                "UTF-8"
            );
        }

        $this->conn->write($message);
    }

    public function writeln($message = null): void
    {
        $this->write($message . "\r\n");
    }

    private function prompt($message, $status): void
    {
        $this->setStatus($status);
        $this->write($message);
    }

    public function systemMsg($message, App $source = null, $withId = false): void
    {
        if (!empty($source)) {
            if ($withId) {
                $nickName = $source->nickName .
                            str_repeat(' ', 12 - $this->multi_strlen($source->nickName));

                $message = sprintf(
                    "[%2s:%3d:********:%s]",
                    $source->channel,
                    $source->getId(),
                    $nickName
                ) . $message;
            } else {
                $message = sprintf("%s ", $source) . $message;
            }
        }

        $this->writeln("-------- " . $message);
    }

    public function statusStdout(): void
    {
        printf("nickName: [%s]" . PHP_EOL, $this->nickName);
        printf("      id: %d" . PHP_EOL, $this->getId());
        printf(" channel: %d" . PHP_EOL, $this->channel);
        printf("scrunble: %d" . PHP_EOL, $this->scrunble);
        printf(" private: %d" . PHP_EOL, $this->private);
        printf("  status: %s" . PHP_EOL, $this->status);
        echo PHP_EOL;
    }

    private function welcomeWorld(): void
    {
        $this->writeln(<<<EOD

/////////////// WELCOME TO ONLINE TALK ///////////////

EOD
        );

    }

    private function subTitle(): void
    {
        $this->writeln(<<<EOD

    ★☆★ようこそＯＬＴへ★☆★


EOD
        );
    }

    private function onEncodeSetting($args = null): void
    {
        if (__METHOD__ == $this->getStatus()) {
            $this->clearStatus();

            if (!empty($args)) {
                switch ($args) {
                    case "OLT" :
                    case "OLT.SJIS" :
                        $this->encode = "SJIS-win";
                        break;
                    case "OLT.EUC" :
                        $this->encode = "eucJP-win";
                        break;
                    case "OLT.UTF8" :
                        $this->encode = null;
                        break;
                    default :
                        $this->encode = false;
                }

                if ($this->encode !== false && empty($this->nickName)) {
                    $this->subTitle();
                    $this->onNickName();
                    return;
                }
            }
        }

        $this->writeln("Enter Service-Name{OLT(=SJIS) or OLT.SJIS or OLT.EUC or OLT.UTF8}");
        $this->prompt("", __METHOD__);
    }

    private function multi_strlen($str): int
    {
        $byte = strlen($str);
        $count = mb_strlen($str, "UTF8");
        return $byte - ($byte - $count) / 2;
    }

    public function __toString(): string
    {
        // 名前の後ろに空白を埋める(sprintf()がマルチバイト非対応の為
        $nickName = $this->nickName .
                    str_repeat(' ', 12 - $this->multi_strlen($this->nickName));

        return sprintf(
            "[%2s:%3d:%s]",
            $this->channel,
            $this->getId(),
            $nickName
        );
    }
}
