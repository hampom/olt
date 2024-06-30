<?php

namespace Olt\Service\Command;

use Olt\Connection\Connection;
use Olt\Service\Channel;

trait OutPutUserList
{
    private function outPutUserList(Connection $conn, false|array $channels): void
    {
        if ($channels === false) {
            $conn->systemMsg("利用者がいません。");
            return;
        }

        foreach ($channels as $no => $channel) {
            $conn->writeln(sprintf("チャンネル %2d (%2d)", $no, count($channel)));
            $conn->writeln();

            $i = 0;
            /** @var Connection $other */
            foreach ($channel as $other) {
                $chString = match (true) {
                    !empty($other->user->scramble) => 'S' . $other->user->scramble,
                    !empty($other->user->private) => 'PT',
                    default => $other->user->channel,
                };

                // 名前の後ろに空白を埋める(sprintf()がマルチバイト非対応の為
                $list = sprintf(
                    "%s【漢字】",
                    str_replace(
                        sprintf('[%2s:', $other->user->channel),
                        '[' . $chString . ':',
                        $other->user
                    )
                );

                if (++$i / 2) {
                    $outCmd = "writeln";
                } else {
                    $outCmd = "write";
                    // indent space
                    $list .= '  ';
                }

                $conn->{$outCmd}($list);
            }

            if (count($channel) % 2) {
                $conn->writeln();
            }

            $scTitles = Channel::getInstance()->getscrambleTitle($no);

            if (!empty($scTitles)) {
                $conn->writeln();
                foreach ($scTitles as $scNo => $title) {
                    $conn->writeln(sprintf("SC(%d) %s", $scNo, $title));
                }
            }
        }
    }
}
