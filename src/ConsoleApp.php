<?php declare(strict_types=1);

namespace olt;

class ConsoleApp extends App
{
    public function write(?string ...$message): void
    {
        if (!empty($this->encode)) {
            $message = mb_convert_variables(
                $message,
                $this->encode,
                "UTF-8"
            );
        }

        $this->conn->write(implode("", $message));
    }
}
