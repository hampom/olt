<?php

namespace Olt;

class Channel
{
    private static $instance;
    private $no = [];
    private $secret = [];

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
        if (empty($this->sc[$channel])) return [];
        foreach ($this->sc[$channel] as $no => $scrunble) {
            $scTitles[$no] = $scrunble['title'];
        }

        return $scTitles;
    }

    public function createScrunble($scCode, $title = null, App $app)
    {
        if (empty($app->channel)) return false;

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
        if (!empty($app->scrunble)) return false;
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
        if (empty($this->no)) return;
        foreach ($this->no[$app->channel] as $key => $val) {
            if ($val == $app) {
                if (count($this->no[$app->channel]) < 2) {
                    unset($this->no[$app->channel]);
                } else {
                    unset($this->no[$app->channel][$key]);
                }
                break;
            }
        }

        return $this;
    }

    public function chat(App $source, $message)
    {
        if (empty(trim($message))) return;
        foreach ($this->no[$source->channel] as $app) {
            if ($app->scrunble !== $source->scrunble) continue;
            if (!empty($source->private)) {
                if ($app->private !== $source->getId() &&
                    $app->getid() !== $source->getid()) {
                    continue;
                }
            } else {
                if (!empty($app->private)) continue;
            }
            $app->writeln(sprintf("%s %s", $source, $message));
        }
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

    public function systemMsg($message, App $source = null)
    {
        if (empty($this->no[$source->channel])) return;

        foreach ($this->no[$source->channel] as $app)
        {
            if ($app == $source) continue;
            $app->systemMsg($message, $source);
        }
    }
}
