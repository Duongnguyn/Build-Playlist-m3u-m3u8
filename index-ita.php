<?php
/*
  index.php

  Questa webapp permette di:
  • Aggiungere nuovi elementi (titolo e URL) alla playlist
  • Visualizzare la playlist in una tabella, con possibilità di modificare ed eliminare ogni elemento (utilizzando modali per la modifica)
  • Importare una playlist da un file JSON
  • Esportare la playlist in vari formati: JSON, .m3u, .m3u8, CSV e SQL

  I dati vengono persistiti in un file JSON (playlist_data.json) che viene creato automaticamente se non esiste.
*/

// Nome del file dove salviamo i dati
$dataFile = 'playlist_data.json';

// Se il file dati non esiste, lo creiamo vuoto
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
}

// Funzione per caricare la playlist dal file JSON
function loadPlaylist() {
    global $dataFile;
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
    return $data;
}

// Funzione per salvare la playlist nel file JSON
function savePlaylist($playlist) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($playlist, JSON_PRETTY_PRINT));
}

// GESTIONE ESPORTAZIONI
// Se viene passato il parametro GET action=export ed è presente il formato, il file verrà generato in output
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
            echo "Formato di esportazione non supportato.";
            exit;
    }
}

// GESTIONE AZIONI VIA FORM (POST)
// Vediamo se è stata inviata una richiesta per aggiungere, modificare, eliminare o importare
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlist = loadPlaylist();
    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $url   = isset($_POST['url']) ? trim($_POST['url']) : '';
        if ($title !== '' && $url !== '') {
            // Usiamo il timestamp come ID (in ambienti reali sarebbe ideale usare un ID univoco)
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
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Gestore Playlist Dinamico</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS da CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h1 class="mb-4">Gestore Playlist Dinamico</h1>

  <!-- Sezione per Aggiungere un Nuovo Elemento -->
  <div class="card mb-3">
    <div class="card-header">Aggiungi Elemento</div>
    <div class="card-body">
      <form method="POST" class="row gy-2 gx-3 align-items-center">
        <input type="hidden" name="action" value="add">
        <div class="col-sm-5">
          <label class="visually-hidden" for="title">Titolo</label>
          <input type="text" class="form-control" id="title" name="title" placeholder="Titolo (es. Film)">
        </div>
        <div class="col-sm-5">
          <label class="visually-hidden" for="url">URL</label>
          <input type="url" class="form-control" id="url" name="url" placeholder="URL del video">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Aggiungi</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Visualizzazione della Playlist in Tabella -->
  <div class="card mb-3">
    <div class="card-header">Playlist</div>
    <div class="card-body">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Titolo</th>
            <th>URL</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php $playlist = loadPlaylist(); ?>
          <?php if (empty($playlist)): ?>
            <tr>
              <td colspan="4" class="text-center">Nessun elemento presente.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($playlist as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['id']) ?></td>
              <td><?= htmlspecialchars($item['title']) ?></td>
              <td><?= htmlspecialchars($item['url']) ?></td>
              <td>
                <!-- Bottone per modificare (apre la modal) -->
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $item['id'] ?>">
                  Modifica
                </button>
                <!-- Form inline per eliminare -->
                <form method="POST" action="index.php" class="d-inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo elemento?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                </form>
              </td>
            </tr>

            <!-- Modal per la Modifica dell'Elemento -->
            <div class="modal fade" id="editModal<?= $item['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $item['id'] ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="index.php">
                    <div class="modal-header">
                      <h5 class="modal-title" id="editModalLabel<?= $item['id'] ?>">Modifica Elemento</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id" value="<?= $item['id'] ?>">
                      <div class="mb-3">
                        <label for="editTitle<?= $item['id'] ?>" class="form-label">Titolo</label>
                        <input type="text" class="form-control" id="editTitle<?= $item['id'] ?>" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label for="editURL<?= $item['id'] ?>" class="form-label">URL</label>
                        <input type="url" class="form-control" id="editURL<?= $item['id'] ?>" name="url" value="<?= htmlspecialchars($item['url']) ?>" required>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                      <button type="submit" class="btn btn-primary">Salva modifiche</button>
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

  <!-- Sezione per Importare la Playlist in formato JSON -->
  <div class="card mb-3">
    <div class="card-header">Importa Playlist (JSON)</div>
    <div class="card-body">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="import">
        <div class="mb-3">
          <label for="jsonData" class="form-label">Inserisci il JSON</label>
          <textarea name="jsonData" id="jsonData" class="form-control" rows="5" placeholder='[{"id":123456789,"title":"Film","url":"http://example.com/video"}]'></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Importa JSON</button>
      </form>
    </div>
  </div>

  <!-- Sezione per le Esportazioni -->
  <div class="card mb-3">
    <div class="card-header">Esporta Playlist</div>
    <div class="card-body">
      <div class="list-group">
        <a href="index.php?action=export&format=json" class="list-group-item list-group-item-action" target="_blank">Esporta in JSON</a>
        <a href="index.php?action=export&format=m3u" class="list-group-item list-group-item-action" target="_blank">Esporta in .m3u</a>
        <a href="index.php?action=export&format=m3u8" class="list-group-item list-group-item-action" target="_blank">Esporta in .m3u8</a>
        <a href="index.php?action=export&format=csv" class="list-group-item list-group-item-action" target="_blank">Esporta in CSV</a>
        <a href="index.php?action=export&format=sql" class="list-group-item list-group-item-action" target="_blank">Esporta in SQL</a>
      </div>
    </div>
  </div>

</div>
<!-- Bootstrap JS Bundle con Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
