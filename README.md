# Web掲示板アプリケーション

これは、PHPとMySQLで動作するシンプルなWeb掲示板です。Dockerを使用して環境を構築します。

## 必須要件

* AWS EC2 インスタンス (Amazon Linux 2推奨)
* Git
* Docker
* Docker Compose

## 構築手順

### 1. EC2インスタンスのセットアップ

1.  AWSマネジメントコンソールからEC2インスタンス（例: `t2.micro`, Amazon Linux 2）を起動します。
2.  セキュリティグループで、以下のインバウンドルールを追加します。
    * `SSH (ポート 22)`: ソースをマイIPに設定
    * `HTTP (ポート 80)`: ソースを `0.0.0.0/0` に設定

### 2. サーバー環境のセットアップ

1.  SSHクライアントでEC2インスタンスに接続します。

    ```bash
    ssh -i "your-key.pem" ec2-user@your-ec2-public-ip
    ```

2.  パッケージを更新し、Gitをインストールします。

    ```bash
    sudo yum update -y
    sudo yum install -y git
    ```

3.  Dockerをインストールし、サービスを開始・有効化します。

    ```bash
    sudo yum install -y docker
    sudo systemctl start docker
    sudo systemctl enable docker
    ```

4.  `ec2-user` を `docker` グループに追加して、`sudo`なしでDockerコマンドを実行できるようにします。

    ```bash
    sudo usermod -aG docker ec2-user
    ```

    **一度SSH接続を切り、再度接続して設定を反映させてください。**

5.  Docker Composeをインストールします。

    ```bash
    sudo curl -L "[https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname](https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname) -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    ```

### 3. アプリケーションのデプロイ

1.  このリポジトリをクローンします。

    ```bash
    git clone https://(あなたのリポジトリのURL).git
    ```

2.  クローンしたディレクトリに移動します。

    ```bash
    cd (リポジトリ名)
    ```

3.  Docker Composeを使ってアプリケーションをビルドし、バックグラウンドで起動します。

    ```bash
    docker-compose up -d --build
    ```

### 4. 動作確認

ブラウザを開き、EC2インスタンスのパブリックIPアドレスにアクセスします。

`http://(あなたのEC2インスタンスのパブリックIPアドレス)/`

掲示板が表示されれば、セットアップは完了です。
