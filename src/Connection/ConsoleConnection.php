<?php

declare(strict_types=1);

namespace Olt\Connection;

final class ConsoleConnection extends Connection
{
    public function write(?string ...$message): void
    {
        if (!empty($this->encode)) {
            $message = mb_convert_variables("UTF-8", $this->encode, $message);
        }

        $this->conn->write(implode("", $message));
    }

    protected function getMessageFromSource($message): string
    {
        return $message;
    }

    protected function getMessageType(): string
    {
        return 'data';
    }
}
