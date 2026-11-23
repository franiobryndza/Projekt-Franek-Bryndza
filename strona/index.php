<?php
require 'db.php';

$page = $_GET['page'] ?? 'home';
if (!in_array($page, ['home', 'players', 'matches', 'table'], true)) $page = 'home';

// ---- Obsługa formularzy (POST, potem przekierowanie) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $back = 'index.php?page=';
    if ($action === 'add_player') {
        $name = trim($_POST['name'] ?? '');
        $pos = $_POST['position'] ?? '';
        $num = (int)($_POST['number'] ?? 0);
        $okPos = in_array($pos, ['Bramkarz', 'Obrońca', 'Pomocnik', 'Napastnik'], true);
        if ($name === '' || !$okPos || $num < 1 || $num > 99) {
            header('Location: ' . $back . 'players&err=invalid'); exit;
        }
        try {
            $pdo->prepare('INSERT INTO players (name, position, number) VALUES (?,?,?)')->execute([$name, $pos, $num]);
        } catch (PDOException $ex) {
            header('Location: ' . $back . 'players&err=number&n=' . $num); exit;
        }
        header('Location: ' . $back . 'players'); exit;
    }
    if ($action === 'delete_player') {
        $pdo->prepare('DELETE FROM players WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . $back . 'players'); exit;
    }
    if ($action === 'add_match') {
        $opp = trim($_POST['opponent'] ?? '');
        $date = $_POST['match_date'] ?? '';
        $gf = (int)($_POST['goals_for'] ?? -1);
        $ga = (int)($_POST['goals_against'] ?? -1);
        $validDate = DateTime::createFromFormat('Y-m-d', $date) !== false;
        if ($opp !== '' && $validDate && $gf >= 0 && $ga >= 0) {
            $pdo->prepare('INSERT INTO matches (opponent, match_date, goals_for, goals_against) VALUES (?,?,?,?)')
                ->execute([$opp, $date, $gf, $ga]);
        }
        header('Location: ' . $back . 'matches'); exit;
    }
    if ($action === 'delete_match') {
        $pdo->prepare('DELETE FROM matches WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . $back . 'matches'); exit;
    }
}

function result_of($m) {
    return $m['goals_for'] > $m['goals_against'] ? 'Wygrana' : ($m['goals_for'] == $m['goals_against'] ? 'Remis' : 'Porażka');
}

$matches = $pdo->query('SELECT * FROM matches ORDER BY match_date DESC, id DESC')->fetchAll();
$tabs = ['home' => 'Strona główna', 'players' => 'Zawodnicy', 'matches' => 'Mecze', 'table' => 'Tabela ligowa'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Football Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <h1>Football Manager</h1>
  <nav>
    <?php foreach ($tabs as $key => $label): ?>
      <a class="tab<?= $page === $key ? ' active' : '' ?>" href="index.php?page=<?= $key ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main>

<?php if ($page === 'home'):
    $playersCount = (int)$pdo->query('SELECT COUNT(*) FROM players')->fetchColumn();
    $wins = count(array_filter($matches, fn($m) => result_of($m) === 'Wygrana'));
    $goals = array_sum(array_column($matches, 'goals_for'));
?>
  <h2>Statystyki drużyny</h2>
  <div class="stats">
    <div class="stat"><b><?= $playersCount ?></b><span>Zawodnicy</span></div>
    <div class="stat"><b><?= count($matches) ?></b><span>Rozegrane mecze</span></div>
    <div class="stat"><b><?= $wins ?></b><span>Zwycięstwa</span></div>
    <div class="stat"><b><?= $goals ?></b><span>Strzelone gole</span></div>
  </div>

<?php elseif ($page === 'players'):
    $q = trim($_GET['q'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM players WHERE name LIKE :q OR position LIKE :q OR number LIKE :q ORDER BY number');
    $stmt->execute([':q' => "%$q%"]);
    $players = $stmt->fetchAll();
?>
  <h2>Zawodnicy</h2>
  <form method="post" class="row">
    <input type="hidden" name="action" value="add_player">
    <input name="name" placeholder="Imię i nazwisko" required maxlength="100">
    <select name="position" aria-label="Pozycja">
      <option>Bramkarz</option><option>Obrońca</option><option>Pomocnik</option><option>Napastnik</option>
    </select>
    <input name="number" type="number" min="1" max="99" placeholder="Numer" required>
    <button class="primary">Dodaj zawodnika</button>
  </form>
  <p class="error">
    <?php if (($_GET['err'] ?? '') === 'number'): ?>Numer <?= (int)($_GET['n'] ?? 0) ?> jest już zajęty. Wybierz inny numer.
    <?php elseif (($_GET['err'] ?? '') === 'invalid'): ?>Wypełnij poprawnie wszystkie pola (numer od 1 do 99).<?php endif; ?>
  </p>
  <form method="get" class="row">
    <input type="hidden" name="page" value="players">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Szukaj zawodnika, pozycji lub numeru">
    <button class="primary">Szukaj</button>
  </form>
  <div class="scroll"><table>
    <thead><tr><th>Nr</th><th>Imię i nazwisko</th><th>Pozycja</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($players as $p): ?>
      <tr><td><?= $p['number'] ?></td><td><?= e($p['name']) ?></td><td><?= e($p['position']) ?></td>
        <td><form method="post" class="inline" onsubmit="return confirm('Usunąć zawodnika?')">
          <input type="hidden" name="action" value="delete_player"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="del">Usuń</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$players): ?><tr><td colspan="4" class="empty">Brak zawodników.</td></tr><?php endif; ?>
    </tbody>
  </table></div>

<?php elseif ($page === 'matches'): ?>
  <h2>Mecze</h2>
  <form method="post" class="row">
    <input type="hidden" name="action" value="add_match">
    <input name="opponent" placeholder="Przeciwnik" required maxlength="100">
    <input name="match_date" type="date" required>
    <input name="goals_for" type="number" min="0" placeholder="Nasze gole" required>
    <input name="goals_against" type="number" min="0" placeholder="Gole rywala" required>
    <button class="primary">Dodaj mecz</button>
  </form>
  <div class="scroll"><table>
    <thead><tr><th>Data</th><th>Przeciwnik</th><th>Wynik</th><th>Rezultat</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($matches as $m): $r = result_of($m); ?>
      <tr><td><?= e($m['match_date']) ?></td><td><?= e($m['opponent']) ?></td>
        <td><?= $m['goals_for'] ?>:<?= $m['goals_against'] ?></td><td class="res <?= $r ?>"><?= $r ?></td>
        <td><form method="post" class="inline" onsubmit="return confirm('Usunąć mecz?')">
          <input type="hidden" name="action" value="delete_match"><input type="hidden" name="id" value="<?= $m['id'] ?>">
          <button class="del">Usuń</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$matches): ?><tr><td colspan="5" class="empty">Brak meczów.</td></tr><?php endif; ?>
    </tbody>
  </table></div>

<?php else:
    $teams = [TEAM => ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0]];
    $add = function (&$t, $gf, $ga) {
        $t['p']++; $t['gf'] += $gf; $t['ga'] += $ga;
        if ($gf > $ga) $t['w']++; elseif ($gf == $ga) $t['d']++; else $t['l']++;
    };
    foreach ($matches as $m) {
        $opp = $m['opponent'];
        $teams[$opp] ??= ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0];
        $add($teams[TEAM], $m['goals_for'], $m['goals_against']);
        $add($teams[$opp], $m['goals_against'], $m['goals_for']);
    }
    $rows = [];
    foreach ($teams as $name => $t) { $t['name'] = $name; $t['pts'] = $t['w']*3 + $t['d']; $t['gd'] = $t['gf'] - $t['ga']; $rows[] = $t; }
    usort($rows, fn($a, $b) => [$b['pts'], $b['gd'], $b['gf'], $a['name']] <=> [$a['pts'], $a['gd'], $a['gf'], $b['name']]);
?>
  <h2>Tabela ligowa</h2>
  <div class="scroll"><table>
    <thead><tr><th>#</th><th>Drużyna</th><th>M</th><th>Z</th><th>R</th><th>P</th><th>BZ</th><th>BS</th><th>RB</th><th>Pkt</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $t): ?>
      <tr class="<?= $t['name'] === TEAM ? 'me' : '' ?>">
        <td><?= $i + 1 ?></td><td><?= e($t['name']) ?></td><td><?= $t['p'] ?></td><td><?= $t['w'] ?></td>
        <td><?= $t['d'] ?></td><td><?= $t['l'] ?></td><td><?= $t['gf'] ?></td><td><?= $t['ga'] ?></td>
        <td><?= ($t['gd'] > 0 ? '+' : '') . $t['gd'] ?></td><td><b><?= $t['pts'] ?></b></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="note">Za zwycięstwo 3 pkt, za remis 1 pkt. Wyniki rywali wynikają z meczów Twojej drużyny.</p>
<?php endif; ?>

</main>
</body>
</html>
