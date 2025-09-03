<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (!isset($_GET['id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: ./");
  return;
}
$id = intval($_GET['id']);

$select_sth = $dbh->prepare('SELECT * FROM bbs_entries WHERE id = :id');
$select_sth->bindParam(':id', $id, PDO::PARAM_INT);
$select_sth->execute();
$entry = $select_sth->fetch();

if (!$entry) {
  // 投稿が見つからない場合はトップページに戻す
  header("HTTP/1.1 302 Found");
  header("Location: ./");
  return;
}

// レスアンカーのリンクを生成する関数
function convert_レスアンカー($body) {
  return preg_replace('/&gt;&gt;(\d+)/', '<a href="./view.php?id=$1">&gt;&gt;$1</a>', $body);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>投稿 No.<?= $entry['id'] ?> - Web掲示板</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1>投稿 No.<?= $entry['id'] ?></h1>
  </header>

  <main>
    <p><a href="./">一覧に戻る</a></p>

    <section class="timeline">
      <div class="post">
        <div class="post-header">
          <span class="post-id">No. <?= $entry['id'] ?></span>
          <span class="post-date"><?= $entry['created_at'] ?></span>
        </div>
        <div class="post-body">
          <p>
            <?php
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
    </section>
  </main>
</body>
</html>
