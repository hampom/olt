<?php

declare(strict_types=1);

namespace Olt\Connection;

final class WebConnection extends Connection
{
    public function write(?string ...$messages): void
    {
        foreach ($messages as $message) {
            if (is_null($message)) {
                $message = "";
            }
            $this->conn->send($message);
        }
    }

    protected function getMessageFromSource($message): string
    {
        return $message->getPayload();
    }

    protected function getMessageType(): string
    {
        return 'message';
    }
}
