<?php declare(strict_types=1);

namespace olt;

class WebApp extends App
{
    public function write(?string ...$message): void
    {
        $this->conn->send(implode("", $message));
    }
}
