<?php

declare(strict_types=1);

namespace Olt\Service\Command\Opening;

use Olt\Connection\Connection;
use Olt\Service\Command\CommandInterface;

final class EncodeSetting implements CommandInterface
{
    public function __construct(
        private Connection $conn,
        private null $command,
    ) {
    }

    public function __invoke(): ?CommandInterface
    {
        $this->conn->writeln("Enter Service-Name{OLT(=SJIS) or OLT.SJIS or OLT.EUC or OLT.UTF8}");

        return $this;
    }

    public function run(string $arg): ?CommandInterface
    {
        $this->conn->encode = match ($arg) {
            "OLT", "OLT.SJIS" => "SJIS-win",
            "OLT.EUC" => "eucJP-win",
            "OLT.UTF8" => null,
            default => false,
        };

        if ($this->conn->encode !== false && empty($this->nickName)) {
            return (new Banner($this->conn, null))();
        }

        return $this;
    }
}
