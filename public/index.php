<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// レスアンカーのリンクを生成する関数
function convert_レスアンカー($body) {
  // >>[数字] の形式を検出し、リンクに変換する
  return preg_replace('/&gt;&gt;(\d+)/', '<a href="./view.php?id=$1">&gt;&gt;$1</a>', $body);
}


if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // アップロードされた画像がある場合
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      // アップロードされたものが画像ではなかった場合
      header("HTTP/1.1 302 Found");
      header("Location: ./");
      return;
    }

    // 元のファイル名から拡張子を取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];
    // 新しいファイル名を決める
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath =  '/var/www/upload/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // 処理が終わったらリダイレクトする
  header("HTTP/1.1 302 Found");
  header("Location: ./");
  return;
}

// 投稿を取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Web掲示板</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1>Web掲示板</h1>
  </header>

  <main>
    <section class="post-form">
      <h2>投稿フォーム</h2>
      <form method="POST" action="./" enctype="multipart/form-data">
        <textarea name="body" required placeholder="ここにメッセージを入力"></textarea>
        <div class="form-actions">
          <input type="file" accept="image/*" name="image" id="imageInput">
          <button type="submit">送信</button>
        </div>
      </form>
    </section>

    <hr>

    <section class="timeline">
      <h2>投稿一覧</h2>
      <?php foreach($select_sth as $entry): ?>
        <div class="post">
          <div class="post-header">
            <span class="post-id">No. <?= $entry['id'] ?></span>
            <span class="post-date"><?= $entry['created_at'] ?></span>
          </div>
          <div class="post-body">
            <p>
              <?php
                // XSS対策でエスケープしてからレスアンカーを有効にする
                $escaped_body = nl2br(htmlspecialchars($entry['body']));
                echo convert_レスアンカー($escaped_body);
              ?>
            </p>
            <?php if(!empty($entry['image_filename'])): ?>
            <div class="post-image">
              <img src="/image/<?= $entry['image_filename'] ?>">
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach ?>
    </section>
  </main>

  <script>
  // 5MBを超える画像をクライアント側で弾き、リサイズする
  document.addEventListener("DOMContentLoaded", () => {
    const imageInput = document.getElementById("imageInput");
    imageInput.addEventListener("change", (event) => {
      if (imageInput.files.length < 1) return;
      const file = imageInput.files[0];

      if (file.size <= 5 * 1024 * 1024) return; // 5MB以下なら何もしない

      alert("5MBを超える画像です。自動的にリサイズします。");

      const reader = new FileReader();
      reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement("canvas");
          const max_size = 1280; // 長辺の最大サイズ
          let width = img.width;
          let height = img.height;
          
          if (width > height) {
            if (width > max_size) {
              height *= max_size / width;
              width = max_size;
            }
          } else {
            if (height > max_size) {
              width *= max_size / height;
              height = max_size;
            }
          }
          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext("2d");
          ctx.drawImage(img, 0, 0, width, height);

          // CanvasからBlobオブジェクトを作成
          canvas.toBlob((blob) => {
            // 新しいFileオブジェクトを作成してinputにセットし直す
            const resizedFile = new File([blob], file.name, {
              type: 'image/jpeg',
              lastModified: Date.now()
            });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(resizedFile);
            imageInput.files = dataTransfer.files;
            alert("リサイズが完了しました。");
          }, 'image/jpeg', 0.85); // JPEG品質85%
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  });
  </script>
</body>
</html>
