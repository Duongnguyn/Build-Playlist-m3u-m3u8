<?php
/*
  index.php

  This webapp allows you to:
  • Add new items (title and URL) to the playlist
  • Display the playlist in a table with the ability to edit and delete each item (using modals for editing)
  • Import a playlist from a JSON file
  • Export the playlist in various formats: JSON, .m3u, .m3u8, CSV, and SQL

  The data is persisted in a JSON file (playlist_data.json), which is automatically created if it does not exist.
*/

// Name of the file where we save the data
$dataFile = 'playlist_data.json';

// If the data file does not exist, create an empty one
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
}

// Function to load the playlist from the JSON file
function loadPlaylist() {
    global $dataFile;
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
    return $data;
}

// Function to save the playlist to the JSON file
function savePlaylist($playlist) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($playlist, JSON_PRETTY_PRINT));
}

// EXPORT HANDLING
// If the GET parameter action=export is passed and a format is provided, the file will be generated for output
if (isset($_GET['action']) && $_GET['action'] === 'export' && isset($_GET['format'])) {
    $format = $_GET['format'];
    $playlist = loadPlaylist();
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="playlist.json"');
            echo json_encode($playlist, JSON_PRETTY_PRINT);
            exit;
        case 'm3u':
            header('Content-Type: audio/x-mpegurl');
            header('Content-Disposition: attachment; filename="playlist.m3u"');
            $content = "#EXTM3U\n";
            foreach ($playlist as $item) {
                $content .= "#EXTINF:-1, {$item['title']}\n{$item['url']}\n";
            }
            echo $content;
            exit;
        case 'm3u8':
            header('Content-Type: application/vnd.apple.mpegurl');
            header('Content-Disposition: attachment; filename="playlist.m3u8"');
            $content = "#EXTM3U\n";
            foreach ($playlist as $item) {
                $content .= "#EXTINF:-1, {$item['title']}\n{$item['url']}\n";
            }
            echo $content;
            exit;
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="playlist.csv"');
            $csv = "id,title,url\n";
            foreach ($playlist as $item) {
                $id    = $item['id'];
                $title = '"'.str_replace('"', '""', $item['title']).'"';
                $url   = '"'.str_replace('"', '""', $item['url']).'"';
                $csv  .= "$id,$title,$url\n";
            }
            echo $csv;
            exit;
        case 'sql':
            header('Content-Type: text/sql');
            header('Content-Disposition: attachment; filename="playlist.sql"');
            $sql = "CREATE TABLE IF NOT EXISTS playlist (\n  id INTEGER PRIMARY KEY,\n  title TEXT,\n  url TEXT\n);\n";
            foreach ($playlist as $item) {
                $id    = $item['id'];
                $title = str_replace("'", "''", $item['title']);
                $url   = str_replace("'", "''", $item['url']);
                $sql  .= "INSERT INTO playlist (id, title, url) VALUES ($id, '$title', '$url');\n";
            }
            echo $sql;
            exit;
        default:
            echo "Export format not supported.";
            exit;
    }
}

// FORM ACTION HANDLING (POST)
// Check if a request to add, edit, delete, or import has been sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlist = loadPlaylist();
    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $url   = isset($_POST['url']) ? trim($_POST['url']) : '';
        if ($title !== '' && $url !== '') {
            // We use the timestamp as the ID (in real applications it would be ideal to use a unique ID)
            $newItem = [
                'id'    => time(),
                'title' => $title,
                'url'   => $url,
            ];
            $playlist[] = $newItem;
            savePlaylist($playlist);
        }
        header('Location: index.php');
        exit;
        
    } elseif ($action === 'edit') {
        $id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $url   = isset($_POST['url']) ? trim($_POST['url']) : '';
        if ($id > 0 && $title !== '' && $url !== '') {
            foreach ($playlist as &$item) {
                if ($item['id'] === $id) {
                    $item['title'] = $title;
                    $item['url']   = $url;
                    break;
                }
            }
            savePlaylist($playlist);
        }
        header('Location: index.php');
        exit;
        
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $playlist = array_filter($playlist, function ($item) use ($id) {
                return $item['id'] !== $id;
            });
            savePlaylist($playlist);
        }
        header('Location: index.php');
        exit;
        
    } elseif ($action === 'import') {
        $jsonData = isset($_POST['jsonData']) ? trim($_POST['jsonData']) : '';
        if ($jsonData !== '') {
            $data = json_decode($jsonData, true);
            if (is_array($data)) {
                $playlist = $data;
                savePlaylist($playlist);
            }
        }
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dynamic Playlist Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS from CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h1 class="mb-4">Dynamic Playlist Manager</h1>

  <!-- Section to Add a New Item -->
  <div class="card mb-3">
    <div class="card-header">Add Item</div>
    <div class="card-body">
      <form method="POST" class="row gy-2 gx-3 align-items-center">
        <input type="hidden" name="action" value="add">
        <div class="col-sm-5">
          <label class="visually-hidden" for="title">Title</label>
          <input type="text" class="form-control" id="title" name="title" placeholder="Title (e.g., Movie)">
        </div>
        <div class="col-sm-5">
          <label class="visually-hidden" for="url">URL</label>
          <input type="url" class="form-control" id="url" name="url" placeholder="Video URL">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Display the Playlist in a Table -->
  <div class="card mb-3">
    <div class="card-header">Playlist</div>
    <div class="card-body">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>URL</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $playlist = loadPlaylist(); ?>
          <?php if (empty($playlist)): ?>
            <tr>
              <td colspan="4" class="text-center">No items found.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($playlist as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['id']) ?></td>
              <td><?= htmlspecialchars($item['title']) ?></td>
              <td><?= htmlspecialchars($item['url']) ?></td>
              <td>
                <!-- Button to Edit (opens the modal) -->
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $item['id'] ?>">
                  Edit
                </button>
                <!-- Inline form to Delete -->
                <form method="POST" action="index.php" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this item?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <!-- Modal for Editing the Item -->
            <div class="modal fade" id="editModal<?= $item['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $item['id'] ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="index.php">
                    <div class="modal-header">
                      <h5 class="modal-title" id="editModalLabel<?= $item['id'] ?>">Edit Item</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id" value="<?= $item['id'] ?>">
                      <div class="mb-3">
                        <label for="editTitle<?= $item['id'] ?>" class="form-label">Title</label>
                        <input type="text" class="form-control" id="editTitle<?= $item['id'] ?>" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label for="editURL<?= $item['id'] ?>" class="form-label">URL</label>
                        <input type="url" class="form-control" id="editURL<?= $item['id'] ?>" name="url" value="<?= htmlspecialchars($item['url']) ?>" required>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Section to Import the Playlist in JSON Format -->
  <div class="card mb-3">
    <div class="card-header">Import Playlist (JSON)</div>
    <div class="card-body">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="import">
        <div class="mb-3">
          <label for="jsonData" class="form-label">Enter JSON Data</label>
          <textarea name="jsonData" id="jsonData" class="form-control" rows="5" placeholder='[{"id":123456789,"title":"Movie","url":"http://example.com/video"}]'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Import JSON</button>
      </form>
    </div>
  </div>

  <!-- Section for Playlist Exports -->
  <div class="card mb-3">
    <div class="card-header">Export Playlist</div>
    <div class="card-body">
      <div class="list-group">
        <a href="index.php?action=export&format=json" class="list-group-item list-group-item-action" target="_blank">Export as JSON</a>
        <a href="index.php?action=export&format=m3u" class="list-group-item list-group-item-action" target="_blank">Export as .m3u</a>
        <a href="index.php?action=export&format=m3u8" class="list-group-item list-group-item-action" target="_blank">Export as .m3u8</a>
        <a href="index.php?action=export&format=csv" class="list-group-item list-group-item-action" target="_blank">Export as CSV</a>
        <a href="index.php?action=export&format=sql" class="list-group-item list-group-item-action" target="_blank">Export as SQL</a>
      </div>
    </div>
  </div>

</div>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
