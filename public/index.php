<?php
// --- 設定 ---
define('POSTS_PER_PAGE', 10); // 1ページあたりの表示件数

// --- データベース接続 ---
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// --- 関数定義 ---
function convert_レスアンカー($body) {
  $escaped_body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
  return preg_replace('/&gt;&gt;(\d+)/', '<a href="./view.php?id=$1">&gt;&gt;$1</a>', $escaped_body);
}

// --- POSTリクエスト処理 (新規投稿) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) === 1) {
      $pathinfo = pathinfo($_FILES['image']['name']);
      $extension = $pathinfo['extension'];
      $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
      $filepath = '/var/www/upload/image/' . $image_filename;
      move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
    }
  }
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([':body' => $_POST['body'], ':image_filename' => $image_filename]);
  header("HTTP/1.1 302 Found");
  header("Location: ./");
  return;
}

// --- GETリクエスト処理 (ページネーションと検索) ---
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$query = isset($_GET['query']) ? $_GET['query'] : '';
$offset = ($page - 1) * POSTS_PER_PAGE;

// 投稿総数を取得 (検索対応)
$count_sql = 'SELECT COUNT(*) FROM bbs_entries';
if ($query !== '') {
  $count_sql .= ' WHERE body LIKE :query';
}
$count_sth = $dbh->prepare($count_sql);
if ($query !== '') {
  $count_sth->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
}
$count_sth->execute();
$total_posts = $count_sth->fetchColumn();
$total_pages = $total_posts > 0 ? ceil($total_posts / POSTS_PER_PAGE) : 1;


// 表示する投稿を取得 (検索・ページネーション対応)
$select_sql = 'SELECT * FROM bbs_entries';
if ($query !== '') {
  $select_sql .= ' WHERE body LIKE :query';
}
$select_sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
$select_sth = $dbh->prepare($select_sql);
if ($query !== '') {
  $select_sth->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
}
$select_sth->bindValue(':limit', POSTS_PER_PAGE, PDO::PARAM_INT);
$select_sth->bindValue(':offset', $offset, PDO::PARAM_INT);
$select_sth->execute();
$entries = $select_sth->fetchAll(PDO::FETCH_ASSOC);

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

    <section class="search-form">
      <h2>投稿を検索</h2>
      <form method="GET" action="./">
        <input type="text" name="query" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="キーワードを入力">
        <button type="submit">検索</button>
      </form>
      <?php if ($query !== ''): ?>
        <p>「<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>」で検索中... <a href="./">検索を解除</a></p>
      <?php endif; ?>
    </section>

    <hr>

    <section class="timeline">
      <h2>投稿一覧</h2>
      <?php if (empty($entries)): ?>
        <p>投稿はまだありません。</p>
      <?php endif; ?>
      <?php foreach($entries as $entry): ?>
        <div class="post">
          <div class="post-header">
            <span class="post-id">No. <?= $entry['id'] ?></span>
            <span class="post-date"><?= $entry['created_at'] ?></span>
          </div>
          <div class="post-body">
            <p><?= convert_レスアンカー($entry['body']) ?></p>
            <?php if(!empty($entry['image_filename'])): ?>
            <div class="post-image">
              <img src="/image/<?= htmlspecialchars($entry['image_filename'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach ?>
    </section>

    <?php if ($total_pages > 1): ?>
    <section class="pagination">
      <div class="pagination-links">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&query=<?= urlencode($query) ?>">&laquo; 前へ</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="current-page"><?= $i ?></span>
          <?php else: ?>
            <a href="?page=<?= $i ?>&query=<?= urlencode($query) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
          <a href="?page=<?= $page + 1 ?>&query=<?= urlencode($query) ?>">次へ &raquo;</a>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

  </main>

  <script>
  // --- 画像リサイズ機能 ---
  document.addEventListener("DOMContentLoaded", () => {
    const imageInput = document.getElementById("imageInput");
    imageInput.addEventListener("change", (event) => {
      if (imageInput.files.length < 1) return;
      const file = imageInput.files[0];
      if (file.size <= 5 * 1024 * 1024) return;
      alert("5MBを超える画像です。自動的にリサイズします。");
      const reader = new FileReader();
      reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement("canvas");
          const max_size = 1280;
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
          canvas.toBlob((blob) => {
            const resizedFile = new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(resizedFile);
            imageInput.files = dataTransfer.files;
            alert("リサイズが完了しました。");
          }, 'image/jpeg', 0.85);
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  });
  </script>
</body>
</html>

