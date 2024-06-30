日本電気株式会社が運営していたパソコン通信サービス「PC-VAN」のチャットサービスを模したチャットサーバーです。

```
/////////////// WELCOME TO ONLINE TALK ///////////////


Enter Service-Name{OLT(=SJIS) or OLT.SJIS or OLT.EUC or OLT.UTF8}
OLT

    ★☆★ようこそＯＬＴへ★☆★



ニックネームを入力してください : hampom

チャンネル番号またはコマンドを入力してください(1-60,E,UA): ua

-------- 現在のご利用者一覧


-------- 利用者がいません。


チャンネル番号またはコマンドを入力してください(1-60,E,UA): 1

-------- どうぞ、ご利用を始めて下さい。

[ 1:328:hampom      ] 懐かしいPC-VANにあったOLT（オンライントーク）を再現しました。
[ 1:328:hampom      ] スクランブルチャットやプライベートトーク、CAなども実装されています。
```

## 利用できるコマンド

| コマンド | 機能                         | 使い方                                                                   |
|------|----------------------------|-----------------------------------------------------------------------|
| ecof | 入力のエコーバックを無効にします           | `/ecof`                                                               |
| ch   | チャンネルを移動します                | `/ch1` <br> チャンネル1に入室                                                 |
| sc   | スクランブルトークに入室します            | `/sc1234` <br> 合言葉1234のスクランブルトークの作成もしくは入室                             |
| pt   | プライベートトークを作成します            | `/pt328` <br> 328のユーザー番号と1対1のチャットを開始 <br> ※ 相手と同じチャンネルに入室している必要があります。 |
| pf   | ユーザー番号を指定しプロフィールを表示します     | `/pu328` <br> 328のユーザー番号のプロフィールを表示。                                   |
| spf  | 自身のプロフィールを登録します            | `/spf` <br>冒頭にピリオド(.)をひとつ入力し送信するとプロフィール登録を完了します。                      |
| date | サーバーの日時を表示します              | `/date`                                                               |
| ua   | 全チャンネルのユーザー一覧を表示します        | `/ua`                                                                 |
| u    | 入室しているチャンネルのユーザー一覧を表示します   | `/u`                                                                  |
| ca   | ユーザー番号を指定し1行メッセージを送信します    | `/ca328` <br> 328のユーザー番号に1行メッセージを送信します                                |
| rca  | 全てのユーザーからの１行メッセージの受信を拒否します | `/rca`                                                                |
| e    | チャットを終了します                 | `/e`                                                                  |

## 起動方法

```
$ php composer.phar install
$ sudo php server.php
```

23番ポートで起動する場合は、root権限が必要になります。ポート番号は`server.php`に記述されていますので、変更が可能です。

## アクセス方法

Telnet: デフォルトでは23番ポートが設定されています。  
※ Linuxのターミナルから接続する場合は、`-E` および、 `-8` オプションを指定する必要があります。
