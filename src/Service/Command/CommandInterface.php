<?php

namespace Olt\Service\Command;

interface CommandInterface
{
    /**
     * コマンドの初期実行
     *
     * @return CommandInterface|null 入力待ちの場合は自分自身を返す
     */
    public function __invoke(): ?CommandInterface;

    /**
     * コマンドの実行
     *
     * @return CommandInterface|null 入力が複数に渡る場合や、再度同一のコマンドを実行する場合などは自分自身(またはコマンド)を返す
     */
    public function run(string $arg): ?CommandInterface;
}
