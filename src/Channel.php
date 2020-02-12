<?php declare(strict_types=1);

namespace olt;

class Channel
{
    /* 通知相手
     *  1. チャンネルメンバー全員(SC,PTを含む)
     *  2. SCメンバーのみ
     *  3. PTメンバーのみ
     *  4. チャンネルメンバーのみ
     */
    const NOTICE_ALL = 1;
    const NOTICE_SC = 2;
    const NOTICE_PT = 3;
    const NOTICE_CH = 4;

    private static $instance;
    private $no = [];
    private $sc = [];

    private function __construct() { }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Channel;
        }

        return self::$instance;
    }

    public function getScrunbleTitle($channel)
    {
        $scTitles = [];
        if (empty($this->sc[$channel])) {
            return $scTitles;
        }

        foreach ($this->sc[$channel] as $no => $scrunble) {
            $scTitles[$no] = $scrunble['title'];
        }

        return $scTitles;
    }

    public function createScrunble($scCode, App $app, $title = null)
    {
        if (empty($app->channel)) {
            return false;
        }

        $no = 0;
        do {
            $no++;
        } while (!empty($this->sc[$app->channel][$no]));

        $this->sc[$app->channel][$no] = [
            'code'  => $scCode,
            'title' => $title
            ];

        return $this->enterScrunble($no, $app);
    }

    public function enterScrunble($no, App $app)
    {
        if (!empty($app->scrunble)) {
            return false;
        }

        $app->scrunble = $no;

        foreach ($this->no[$app->channel] as $app) {
            $app->statusStdout();
            if ($app->scrunble == $no) {
                $app->systemMsg("がスクランブルモードに加わりました。", $app);
            }
        }
        return true;
    }

    public function findScrunbleByCode($channel, $scCode)
    {
        if (empty($this->sc)) return null;

        foreach ($this->sc[$channel] as $no => $scrunble) {
            if ($scrunble['code'] === $scCode) {
                return $no;
            }
        }

        return null;
    }

    public function enter($channel, App $app)
    {
        if (!is_null($app->channel)) {
            $message = "がチャンネルを移動しました。";
            $this->systemMsg($message, $app);
            $this->out($app);
        } else {
            $app->writeln();
            $app->systemMsg("どうぞ、ご利用を始めて下さい。");
        }

        $this->no[$channel][] = $app;
        $app->channel = $channel;

        return $this;
    }

    public function out(App $app)
    {
        if (empty($this->no)) {
            return $this;
        }

        // スクランプル・プライベートトークを強制解除
        $ptAppId = $app->private;
        $app->scrunble = $app->private = null;

        $scCount = [];
        foreach ($this->no[$app->channel] as $key => $val) {
            // スクランブル参加人数計上
            if (!isset($scCount[$val->scrunble])) {
                $scCount[$val->scrunble] = 0;
            }
            $scCount[$val->scrunble] += 1;

            if ($val == $app) {
                if (count($this->no[$app->channel]) < 2) {
                    unset($this->no[$app->channel]);
                } else {
                    unset($this->no[$app->channel][$key]);
                }

                // 退出する本人はスクランブル人数に加算しない
                $scCount[$val->scrunble] -= 1;
            }
        }

        // ゼロ人のスクランブルを解散する
        foreach ($scCount as $no => $count) {
            if ($count == 0) {
                unset($this->sc[$no]);
            }
        }

        // 1人になたプライベートトークを強制解除
        if (!empty($ptAppId)) {
            foreach ($this->getUserList() as $members) {
                /** @var App $member */
                foreach ($members as $member) {
                    if ($ptAppId == $member->getId()) {
                        $member->private = null;
                        break 2;
                    }
                }
            }
        }

        return $this;
    }

    public function chat(App $source, $message)
    {
        if (!empty($source->private)) {
            return;
        }

        /** @var App $app */
        foreach ($this->no[$source->channel] as $app) {
            if ($app->scrunble !== $source->scrunble) {
                continue;
            }

            if (!empty($app->private)) {
                continue;
            }

            $app->writeln(sprintf("%s %s", $source, $message));
        }
    }

    public function pt(App $source, $message)
    {
        if (empty($source->private)) {
            return;
        }

        foreach (Channel::getInstance()->getUserList() as $members) {
            foreach ($members as $member) {
                if ($source->private == $member->getId()) {
                    $to = $member;
                    break 2;
                }
            }
        }
        if (empty($to) || !is_object($to)) {
            $this->writeln("エラーです");
            return;
        }

        $message = sprintf("%s %s", $source, $message);
        $to->writeln($message);
        $source->writeln($message);
    }

    public function getUserList($channel = null)
    {
        if (!empty($channel)) {
            return !empty($this->no[$channel])
                ? [$channel => $this->no[$channel]]
                : false;
        }

        return !empty($this->no)
            ? $this->no
            : false;
    }

    /**
     * 条件
     *  0. システムのおしらせ: フラグ1
     *  1. SCメンバーのアクション(終了・移動): フラグ 2
     *  2. PTメンバーのアクション(終了・移動): フラグ 3
     *  3. チャンネルメンバー(SC,PTを除く)のアクション(終了・移動 ): フラグ 4
     *
     * @param string $message
     * @param App|null $source
     */
    public function systemMsg($message, App $source)
    {
        if (empty($this->no[$source->channel])) {
            return;
        }

        $notice = null;

        // チャンネルメンバーのアクション： チャンネルメンバーのみ
        if (empty($source->scrunble) && empty($source->private)) {
            $notice = self::NOTICE_CH;
        }

        // SCメンバーのアクション： 同室のSCメンバーおよびチャンネルメンバー
        if (!empty($source->scrunble)) {
            $notice = self::NOTICE_SC;
        }

        // PTメンバーのアクション： 同室のPTメンバーおよびチャンネルメンバー
        if (!empty($source->private)) {
            $notice = self::NOTICE_CH;
        }

        if (is_null($notice)) {
            return;
        }

        /** @var App $app */
        foreach ($this->no[$source->channel] as $app)
        {
            // チャンネルメンバーのアクションはチャンネルメンバーにのみ通知する
            if ($notice == self::NOTICE_CH) {
                if (!empty($app->scrunble) || !empty($app->private)) {
                    continue;
                }
            }

            if ($notice == self::NOTICE_SC) {
                if (!empty($app->scrunble) && $app->scrunble != $source->scrunble) {
                    continue;
                }
            }

            // 本人には通知しない
            if ($app === $source) {
                continue;
            }

            $app->systemMsg($message, $source);
        }
    }
}
