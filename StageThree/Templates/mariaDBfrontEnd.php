<?php
/**
 * CSP450 Stage 3 - Instrument Lookup
 * Student: catalan (UID 231)
 * Server:  catalan-Server (172.16.57.254)
 */
session_start();
$db_name = 'inventory';
$db_user = 'csp450ro';
$db_pass = 'csp450ro';
$db_port = 3306;
$my_uid = 231;

/* ── AJAX: Network scan endpoint ── */
if (isset($_GET['action']) && $_GET['action'] === 'scan') {
  header('Content-Type: application/json');
  $uid_from = max(1, intval($_GET['uid_from'] ?? 1));
  $uid_to = min(500, intval($_GET['uid_to'] ?? 120));
  if ($uid_to < $uid_from)
    $uid_to = $uid_from;

  // Build candidate IPs from UID range
  $candidates = [];
  for ($uid = $uid_from; $uid <= $uid_to; $uid++) {
    $octet3 = intdiv($uid, 4);
    $host = ($uid % 4) * 64 + 62;
    $ip = "172.16.{$octet3}.{$host}";
    $candidates[$uid] = $ip;
  }

  // Phase 1: Non-blocking parallel port scan
  $sockets = [];
  foreach ($candidates as $uid => $ip) {
    $sock = @stream_socket_client(
      "tcp://{$ip}:{$db_port}",
      $errno,
      $errstr,
      0,
      STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
    );
    if ($sock !== false) {
      stream_set_blocking($sock, false);
      $sockets[$uid] = ['socket' => $sock, 'ip' => $ip];
    }
  }

  $reachable = [];
  if (!empty($sockets)) {
    $deadline = microtime(true) + 2.0; // 2-second window
    while (!empty($sockets) && microtime(true) < $deadline) {
      $read = null;
      $write = array_column($sockets, 'socket');
      $except = null;
      $remaining = max(0, $deadline - microtime(true));
      $tv_sec = (int) $remaining;
      $tv_usec = (int) (($remaining - $tv_sec) * 1000000);
      if (@stream_select($read, $write, $except, $tv_sec, $tv_usec) > 0) {
        foreach ($sockets as $uid => $info) {
          if (in_array($info['socket'], $write, true)) {
            // Port is open — mark as reachable
            $reachable[$uid] = $info['ip'];
            fclose($info['socket']);
            unset($sockets[$uid]);
          }
        }
      }
    }
    // Close remaining sockets
    foreach ($sockets as $info) {
      @fclose($info['socket']);
    }
  }

  // Phase 2: Verify PDO connectivity on reachable IPs
  $servers = [];
  foreach ($reachable as $uid => $ip) {
    try {
      $dsn = "mysql:host={$ip};port={$db_port};dbname={$db_name};charset=utf8mb4";
      $test = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2,
      ]);
      $servers[] = ['uid' => $uid, 'ip' => $ip, 'is_you' => ($uid === $my_uid)];
      $test = null;
    } catch (PDOException $e) {
      // Port open but credentials/db don't match — skip
    }
  }

  // Sort by UID
  usort($servers, function ($a, $b) {
    return $a['uid'] - $b['uid']; });

  // Cache in session (session already started at top of file)
  $_SESSION['discovered_servers'] = $servers;

  echo json_encode(['servers' => $servers, 'scanned' => count($candidates)]);
  exit;
}


if (!isset($_SESSION['recent_searches']))
  $_SESSION['recent_searches'] = [];
if (isset($_GET['clear_recent'])) {
  $idx = intval($_GET['clear_recent']);
  if (isset($_SESSION['recent_searches'][$idx]))
    array_splice($_SESSION['recent_searches'], $idx, 1);
  header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
  exit;
}

$results = [];
$error_msg = '';
$searched = false;
$server_ip = '';
$inst_type = '';
$condition = '';
$max_price = '';

if (isset($_GET['replay'])) {
  $idx = intval($_GET['replay']);
  if (isset($_SESSION['recent_searches'][$idx])) {
    $r = $_SESSION['recent_searches'][$idx];
    $server_ip = $r['server_ip'];
    $inst_type = $r['inst_type'];
    $condition = $r['condition'];
    $max_price = $r['max_price'];
    $searched = true;
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $searched = true;
  $server_ip = trim($_POST['server_ip'] ?? '');
  $inst_type = trim($_POST['inst_type'] ?? '');
  $condition = trim($_POST['condition'] ?? '');
  $max_price = trim($_POST['max_price'] ?? '');
}

if ($searched && empty($error_msg)) {
  if (empty($server_ip) || !filter_var($server_ip, FILTER_VALIDATE_IP)) {
    $error_msg = 'Please enter a valid Database Server IP address.';
  } else {
    try {
      $dsn = "mysql:host={$server_ip};port={$db_port};dbname={$db_name};charset=utf8mb4";
      $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
      ]);
      $sql = "SELECT id, instrument_type, instcondition, price FROM instruments WHERE 1=1";
      $params = [];
      if (!empty($inst_type)) {
        $sql .= " AND instrument_type LIKE :inst_type";
        $params[':inst_type'] = '%' . $inst_type . '%';
      }
      if (!empty($condition)) {
        $sql .= " AND instcondition LIKE :cond";
        $params[':cond'] = '%' . $condition . '%';
      }
      if (!empty($max_price) && floatval($max_price) > 0) {
        $sql .= " AND price <= :maxp";
        $params[':maxp'] = floatval($max_price);
      }
      $sql .= " ORDER BY price ASC";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $results = $stmt->fetchAll();

      $parts = [];
      if ($inst_type)
        $parts[] = $inst_type;
      if ($condition)
        $parts[] = ucfirst($condition);
      if ($max_price && floatval($max_price) > 0)
        $parts[] = '≤$' . $max_price;
      $label = empty($parts) ? 'All @ ' . $server_ip : implode(' · ', $parts) . ' @ ' . $server_ip;
      $search_entry = ['server_ip' => $server_ip, 'inst_type' => $inst_type, 'condition' => $condition, 'max_price' => $max_price, 'label' => $label];
      foreach ($_SESSION['recent_searches'] as $k => $v) {
        if ($v['label'] === $label) {
          array_splice($_SESSION['recent_searches'], $k, 1);
          break;
        }
      }
      array_unshift($_SESSION['recent_searches'], $search_entry);
      $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 5);
    } catch (PDOException $e) {
      $error_msg = 'Database connection failed: ' . htmlspecialchars($e->getMessage());
    }
  }
}
$total_results = count($results);
$min_price = $total_results > 0 ? min(array_column($results, 'price')) : 0;
$max_price_found = $total_results > 0 ? max(array_column($results, 'price')) : 0;
// Populate instrument type autocomplete from the connected database
$instrument_types = [];

if (isset($pdo)) {
  try {
    $db_types = $pdo->query(
      "SELECT DISTINCT instrument_type FROM instruments ORDER BY instrument_type ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($db_types)) {
      $instrument_types = $db_types;
    }
  } catch (PDOException $e) {
    // Keep fallback list on error
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instrument Lookup — Musical Instrument Sales Co.</title>
  <style>
    :root[data-theme="light"] {
      --bg-primary: #FAF9F7;
      --bg-secondary: #FFFFFF;
      --bg-tertiary: #F5F3EF;
      --bg-hover: #EDEAE4;
      --text-primary: #1A1613;
      --text-secondary: #5C5651;
      --text-tertiary: #8C857E;
      --border: #E5E0D9;
      --border-focus: #C4A77D;
      --accent: #C4A77D;
      --accent-hover: #B3956A;
      --accent-text: #FFFFFF;
      --stat-bg: #F0EDE8;
      --table-header-bg: #1A1613;
      --table-header-text: #FAF9F7;
      --table-stripe: #FAFAF8;
      --shadow-sm: 0 1px 3px rgba(26, 22, 19, 0.04);
      --shadow-md: 0 4px 16px rgba(26, 22, 19, 0.06);
      --tag-bg: #F0EDE8;
      --tag-text: #5C5651;
      --error-bg: #FFF5F5;
      --error-text: #C53030;
      --cond-new-bg: #E8F5E9;
      --cond-new-text: #2E7D32;
      --cond-used-bg: #FFF3E0;
      --cond-used-text: #E65100;
      --cond-refurb-bg: #E3F2FD;
      --cond-refurb-text: #1565C0
    }

    :root[data-theme="dark"] {
      --bg-primary: #1A1613;
      --bg-secondary: #252220;
      --bg-tertiary: #2E2A27;
      --bg-hover: #3A3532;
      --text-primary: #F5F3EF;
      --text-secondary: #B8B2AA;
      --text-tertiary: #8C857E;
      --border: #3A3532;
      --border-focus: #C4A77D;
      --accent: #C4A77D;
      --accent-hover: #D4B98E;
      --accent-text: #1A1613;
      --stat-bg: #2E2A27;
      --table-header-bg: #2E2A27;
      --table-header-text: #F5F3EF;
      --table-stripe: #222019;
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.2);
      --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
      --tag-bg: #3A3532;
      --tag-text: #B8B2AA;
      --error-bg: #3B1515;
      --error-text: #FC8181;
      --cond-new-bg: #1B3A1E;
      --cond-new-text: #81C784;
      --cond-used-bg: #3E2100;
      --cond-used-text: #FFB74D;
      --cond-refurb-bg: #0D2744;
      --cond-refurb-text: #64B5F6
    }

    /* ── Hide native number spinners ── */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    input[type="number"] {
      -moz-appearance: textfield;
    }

    /* ── Price input wrapper ── */
    .price-input-wrap {
      display: flex;
      align-items: center;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--bg-primary);
      transition: all .3s ease;
      overflow: hidden;
    }

    .price-input-wrap:focus-within {
      border-color: var(--border-focus);
      box-shadow: 0 0 0 3px rgba(196, 167, 125, .15);
    }

    .price-prefix {
      padding: 11px 0 11px 14px;
      font-size: 15px;
      font-weight: 600;
      color: var(--text-tertiary);
      user-select: none;
      line-height: 1;
    }

    .price-input-wrap input {
      flex: 1;
      border: none !important;
      background: transparent !important;
      box-shadow: none !important;
      padding: 11px 8px;
      min-width: 0;
    }

    .price-input-wrap input:focus {
      outline: none;
      border: none !important;
      box-shadow: none !important;
    }

    .price-stepper {
      display: flex;
      flex-direction: column;
      border-left: 1px solid var(--border);
    }

    .price-stepper button {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 50%;
      background: transparent;
      border: none;
      cursor: pointer;
      color: var(--text-tertiary);
      font-size: 11px;
      transition: all .2s ease;
      padding: 0;
    }

    .price-stepper button:hover {
      background: var(--bg-hover);
      color: var(--accent);
    }

    .price-stepper button:first-child {
      border-bottom: 1px solid var(--border);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      line-height: 1.6;
      transition: background .35s ease, color .35s ease
    }

    .page-wrapper {
      max-width: 860px;
      margin: 0 auto;
      padding: 48px 24px 80px
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 48px
    }

    .brand {
      display: flex;
      flex-direction: column;
      gap: 4px
    }

    .brand-name {
      font-size: 13px;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--accent)
    }

    h1 {
      font-size: 32px;
      font-weight: 700;
      letter-spacing: -.02em;
      line-height: 1.2
    }

    .page-subtitle {
      font-size: 15px;
      color: var(--text-tertiary);
      margin-top: 4px
    }

    .theme-toggle {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 14px;
      border-radius: 24px;
      background: var(--bg-tertiary);
      border: 1px solid var(--border);
      cursor: pointer;
      transition: all .3s ease;
      color: var(--text-secondary);
      font-size: 13px;
      font-weight: 500;
      flex-shrink: 0;
      margin-top: 6px
    }

    .theme-toggle:hover {
      background: var(--bg-hover)
    }

    .theme-toggle svg {
      width: 16px;
      height: 16px
    }

    .search-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 24px;
      transition: all .3s ease
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px
    }

    .form-group.full-width {
      grid-column: 1/-1
    }

    .form-group label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-secondary);
      letter-spacing: .02em
    }

    .form-group input,
    .form-group select {
      padding: 11px 14px;
      font-size: 15px;
      font-family: inherit;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--bg-primary);
      color: var(--text-primary);
      transition: all .3s ease;
      -webkit-appearance: none;
      appearance: none
    }

    .form-group select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238C857E' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--border-focus);
      box-shadow: 0 0 0 3px rgba(196, 167, 125, .15)
    }

    .form-group input::placeholder {
      color: var(--text-tertiary)
    }

    .btn-search {
      grid-column: 1/-1;
      padding: 13px 24px;
      background: var(--accent);
      color: var(--accent-text);
      font-size: 15px;
      font-weight: 600;
      font-family: inherit;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: all .3s ease;
      letter-spacing: .01em;
      margin-top: 4px
    }

    .btn-search:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md)
    }

    .btn-search:active {
      transform: translateY(0)
    }

    .recent-section {
      margin-bottom: 32px;
      animation: fadeSlideIn .4s ease
    }

    .section-label {
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--text-tertiary);
      margin-bottom: 10px
    }

    .recent-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px
    }

    .recent-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: var(--tag-bg);
      color: var(--tag-text);
      border: 1px solid var(--border);
      border-radius: 20px;
      font-size: 13px;
      text-decoration: none;
      transition: all .3s ease
    }

    .recent-tag:hover {
      background: var(--bg-hover);
      border-color: var(--accent);
      color: var(--text-primary)
    }

    .tag-x {
      font-size: 11px;
      opacity: .5;
      text-decoration: none;
      color: inherit;
      margin-left: 2px
    }

    .tag-x:hover {
      opacity: 1
    }

    .stats-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
      flex-wrap: wrap;
      animation: fadeSlideIn .4s ease
    }

    .stat-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: var(--stat-bg);
      border-radius: 10px;
      font-size: 13px;
      color: var(--text-secondary)
    }

    .stat-chip strong {
      color: var(--text-primary);
      font-weight: 700
    }

    .error-msg {
      padding: 14px 20px;
      background: var(--error-bg);
      color: var(--error-text);
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 14px;
      animation: fadeSlideIn .4s ease
    }

    .results-wrapper {
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: all .3s ease;
      animation: fadeSlideIn .5s ease
    }

    table {
      width: 100%;
      border-collapse: collapse
    }

    thead th {
      background: var(--table-header-bg);
      color: var(--table-header-text);
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      text-align: left;
      padding: 14px 20px;
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
      transition: all .3s ease
    }

    thead th:hover {
      opacity: .85
    }

    .sort-icon {
      display: inline-block;
      margin-left: 6px;
      font-size: 10px;
      opacity: .4
    }

    thead th.sorted .sort-icon {
      opacity: 1
    }

    tbody td {
      padding: 14px 20px;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
      transition: all .3s ease
    }

    tbody tr {
      transition: background .3s ease
    }

    tbody tr:hover {
      background: var(--bg-hover)
    }

    tbody tr:last-child td {
      border-bottom: none
    }

    tbody tr:nth-child(even) {
      background: var(--table-stripe)
    }

    tbody tr:nth-child(even):hover {
      background: var(--bg-hover)
    }

    .price-cell {
      font-variant-numeric: tabular-nums;
      font-weight: 600;
      color: var(--accent)
    }

    .condition-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .02em;
      text-transform: capitalize
    }

    .condition-new {
      background: var(--cond-new-bg);
      color: var(--cond-new-text)
    }

    .condition-used {
      background: var(--cond-used-bg);
      color: var(--cond-used-text)
    }

    .condition-refurbished {
      background: var(--cond-refurb-bg);
      color: var(--cond-refurb-text)
    }

    .empty-state {
      text-align: center;
      padding: 64px 24px;
      animation: fadeSlideIn .4s ease
    }

    .empty-icon {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: .3
    }

    .empty-state p {
      color: var(--text-tertiary);
      font-size: 15px
    }

    .footer {
      text-align: center;
      margin-top: 48px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
      font-size: 12px;
      color: var(--text-tertiary);
      letter-spacing: .02em
    }

    .footer span {
      color: var(--accent)
    }

    @keyframes fadeSlideIn {
      from {
        opacity: 0;
        transform: translateY(12px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    .row-anim {
      animation: fadeSlideIn .3s ease both
    }

    /* ── Export & Print buttons ── */
    .results-actions {
      display: flex;
      gap: 10px;
      margin-top: 16px;
      animation: fadeSlideIn .4s ease;
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--bg-secondary);
      color: var(--text-secondary);
      cursor: pointer;
      transition: all .3s ease;
      letter-spacing: .01em;
    }

    .btn-action:hover {
      background: var(--bg-hover);
      border-color: var(--accent);
      color: var(--text-primary);
    }

    .btn-action svg {
      width: 14px;
      height: 14px;
    }

    /* ── Print styles ── */
    @media print {
      body {
        background: #fff !important;
        color: #000 !important;
      }

      .header,
      .search-card,
      .recent-section,
      .results-actions,
      .theme-toggle,
      .footer {
        display: none !important;
      }

      .page-wrapper {
        max-width: 100%;
        padding: 0;
      }

      .results-wrapper {
        border: none;
        box-shadow: none;
      }

      .stats-bar {
        margin-bottom: 12px;
      }

      .stat-chip {
        background: #f0f0f0 !important;
        color: #333 !important;
      }

      .stat-chip strong {
        color: #000 !important;
      }

      thead th {
        background: #333 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      tbody td {
        border-bottom: 1px solid #ddd !important;
      }

      .price-cell {
        color: #000 !important;
      }

      .condition-badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 16px;
        font-size: 18px;
        font-weight: 700;
      }
    }

    .print-header {
      display: none;
    }

    /* ── Network scan UI ── */
    .scan-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .btn-scan {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      border: 1px solid var(--accent);
      border-radius: 10px;
      background: transparent;
      color: var(--accent);
      cursor: pointer;
      transition: all .3s ease;
      white-space: nowrap;
    }

    .btn-scan:hover {
      background: var(--accent);
      color: var(--accent-text);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .btn-scan:active {
      transform: translateY(0)
    }

    .btn-scan:disabled {
      opacity: .5;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .btn-scan svg {
      width: 14px;
      height: 14px;
    }

    .scan-range {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: var(--text-tertiary);
    }

    .scan-range input {
      width: 54px;
      padding: 6px 8px;
      font-size: 12px;
      font-family: inherit;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--bg-primary);
      color: var(--text-primary);
      text-align: center;
      transition: border-color .3s ease;
    }

    .scan-range input:focus {
      outline: none;
      border-color: var(--border-focus);
    }

    .scan-status {
      font-size: 12px;
      color: var(--text-tertiary);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .scan-spinner {
      width: 14px;
      height: 14px;
      border: 2px solid var(--border);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin .8s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg)
      }
    }

    .discovered-servers {
      margin-top: 10px;
      animation: fadeSlideIn .4s ease;
    }

    .discovered-label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--text-tertiary);
      margin-bottom: 6px;
    }

    .server-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .server-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      background: var(--tag-bg);
      color: var(--tag-text);
      border: 1px solid var(--border);
      border-radius: 18px;
      font-size: 12px;
      font-family: inherit;
      cursor: pointer;
      transition: all .3s ease;
    }

    .server-tag:hover {
      background: var(--accent);
      color: var(--accent-text);
      border-color: var(--accent);
    }

    .server-tag .tag-you {
      font-size: 10px;
      font-weight: 700;
      background: var(--accent);
      color: var(--accent-text);
      padding: 1px 6px;
      border-radius: 8px;
    }

    .server-tag:hover .tag-you {
      background: var(--accent-text);
      color: var(--accent);
    }

    .server-tag .tag-uid {
      opacity: .6;
    }

    .scan-empty {
      font-size: 12px;
      color: var(--text-tertiary);
      font-style: italic;
      margin-top: 6px;
    }

    @media(max-width:600px) {
      .form-grid {
        grid-template-columns: 1fr
      }

      .header {
        flex-direction: column;
        gap: 16px
      }

      h1 {
        font-size: 26px
      }

      .results-actions {
        flex-direction: column;
      }

      .scan-row {
        flex-direction: column;
        align-items: stretch;
      }
    }
  </style>
</head>

<body>
  <div class="page-wrapper">
    <div class="header">
      <div class="brand">
        <div class="brand-name">Musical Instrument Sales Co.</div>
        <h1>Instrument Lookup</h1>
        <p class="page-subtitle">Search inventory across all connected branches</p>
      </div>
      <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
        <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5" />
          <line x1="12" y1="1" x2="12" y2="3" />
          <line x1="12" y1="21" x2="12" y2="23" />
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
          <line x1="1" y1="12" x2="3" y2="12" />
          <line x1="21" y1="12" x2="23" y2="12" />
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
        </svg>
        <svg id="moonIcon" style="display:none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
        <span id="themeLabel">Dark</span>
      </button>
    </div>

    <div class="search-card">
      <form method="POST" action="">
        <div class="form-grid">
          <div class="form-group full-width">
            <label for="server_ip">Database Server IP</label>
            <input type="text" id="server_ip" name="server_ip" placeholder="e.g. 172.16.20.254"
              value="<?= htmlspecialchars($server_ip) ?>" required>
            <div class="scan-row">
              <button type="button" class="btn-scan" id="scanBtn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="2" y1="12" x2="22" y2="12" />
                  <path
                    d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                Scan Network
              </button>
              <div class="scan-range">
                UID
                <input type="number" id="uidFrom" value="1" min="1" max="500">
                to
                <input type="number" id="uidTo" value="120" min="1" max="500">
              </div>
              <div class="scan-status" id="scanStatus" style="display:none">
                <div class="scan-spinner"></div>
                <span id="scanStatusText">Scanning…</span>
              </div>
            </div>
            <div id="discoveredContainer" style="display:none" class="discovered-servers">
              <div class="discovered-label">Available Servers</div>
              <div class="server-tags" id="serverTags"></div>
            </div>
            <?php if (!empty($_SESSION['discovered_servers'])): ?>
              <div class="discovered-servers">
                <div class="discovered-label">Cached Servers (from last scan)</div>
                <div class="server-tags">
                  <?php foreach ($_SESSION['discovered_servers'] as $srv): ?>
                    <button type="button" class="server-tag"
                      onclick="document.getElementById('server_ip').value='<?= $srv['ip'] ?>'">
                      <?= htmlspecialchars($srv['ip']) ?>
                      <span class="tag-uid">UID
                        <?= $srv['uid'] ?>
                      </span>
                      <?php if ($srv['is_you']): ?><span class="tag-you">You</span>
                      <?php endif; ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="inst_type">Instrument Type</label>
            <input type="text" id="inst_type" name="inst_type" placeholder="e.g. Guitar, Sax, Trumpet"
              value="<?= htmlspecialchars($inst_type) ?>" list="instrumentTypes" autocomplete="nope">
            <datalist id="instrumentTypes">
              <?php foreach ($instrument_types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>">
                <?php endforeach; ?>
            </datalist>
          </div>
          <div class="form-group">
            <label for="condition">Condition</label>
            <select id="condition" name="condition">
              <option value="">Any Condition</option>
              <option value="new" <?= $condition === 'new' ? 'selected' : '' ?>>New</option>
              <option value="used" <?= $condition === 'used' ? 'selected' : '' ?>>Used</option>
              <option value="refurbished" <?= $condition === 'refurbished' ? 'selected' : '' ?>>Refurbished</option>
            </select>
          </div>
          <div class="form-group full-width">
            <label for="max_price">Maximum Price</label>
            <div class="price-input-wrap">
              <span class="price-prefix">$</span>
              <input type="number" id="max_price" name="max_price" placeholder="No limit" min="0" step="50"
                value="<?= htmlspecialchars($max_price) ?>" list="pricePresets">
              <datalist id="pricePresets">
                <option value="100" label="Under $100">
                <option value="250" label="Under $250">
                <option value="500" label="Under $500">
                <option value="1000" label="Under $1,000">
                <option value="2500" label="Under $2,500">
                <option value="5000" label="Under $5,000">
              </datalist>
              <div class="price-stepper">
                <button type="button" onclick="stepPrice(50)" title="Increase by $50">&#9650;</button>
                <button type="button" onclick="stepPrice(-50)" title="Decrease by $50">&#9660;</button>
              </div>
            </div>
          </div>
          <button type="submit" class="btn-search">Search Inventory</button>
        </div>
      </form>
    </div>

    <?php if (!empty($_SESSION['recent_searches'])): ?>
      <div class="recent-section">
        <div class="section-label">Recent Searches</div>
        <div class="recent-tags">
          <?php foreach ($_SESSION['recent_searches'] as $idx => $rs): ?>
            <span class="recent-tag">
              <a href="?replay=<?= $idx ?>"
                style="text-decoration:none;color:inherit"><?= htmlspecialchars($rs['label']) ?></a>
              <a class="tag-x" href="?clear_recent=<?= $idx ?>" title="Remove">&times;</a>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($searched && !empty($error_msg)): ?>
      <div class="error-msg"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="print-header">Musical Instrument Sales Co. — Inventory Report</div>

    <?php if ($searched && empty($error_msg) && $total_results > 0): ?>
      <div class="stats-bar">
        <div class="stat-chip"><strong><?= $total_results ?></strong>&nbsp;result<?= $total_results !== 1 ? 's' : '' ?>
          found</div>
        <div class="stat-chip">Price
          range:&nbsp;<strong>$<?= number_format($min_price, 2) ?></strong>&nbsp;&ndash;&nbsp;<strong>$<?= number_format($max_price_found, 2) ?></strong>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($searched && empty($error_msg)): ?>
      <?php if ($total_results === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">&#9835;</div>
          <p>No instruments found matching your criteria.<br>Try adjusting your filters.</p>
        </div>
      <?php else: ?>
        <div class="results-wrapper">
          <table id="resultsTable">
            <thead>
              <tr>
                <th data-col="0" data-type="num">ID <span class="sort-icon">&#9650;</span></th>
                <th data-col="1" data-type="str">Instrument <span class="sort-icon">&#9650;</span></th>
                <th data-col="2" data-type="str">Condition <span class="sort-icon">&#9650;</span></th>
                <th data-col="3" data-type="num" class="sorted">Price <span class="sort-icon">&#9650;</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $i => $row):
                $delay = min($i * 40, 400);
                $cond = strtolower(trim($row['instcondition']));
                $cond_class = 'condition-used';
                if ($cond === 'new')
                  $cond_class = 'condition-new';
                elseif ($cond === 'refurbished')
                  $cond_class = 'condition-refurbished';
                ?>
                <tr class="row-anim" style="animation-delay: <?= $delay ?>ms">
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['instrument_type']) ?></td>
                  <td><span class="condition-badge <?= $cond_class ?>"><?= htmlspecialchars($row['instcondition']) ?></span>
                  </td>
                  <td class="price-cell">$<?= number_format($row['price'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="results-actions">
          <button class="btn-action" id="exportCsvBtn" title="Download results as CSV">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="7 10 12 15 17 10" />
              <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Export CSV
          </button>
          <button class="btn-action" id="printBtn" title="Print results">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 6 2 18 2 18 9" />
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
              <rect x="6" y="14" width="12" height="8" />
            </svg>
            Print
          </button>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="footer">CSP450 &mdash; <span>dtanguyen2</span></div>
  </div>

  <script>
    /* ── Theme persistence via localStorage ── */
    ( function () {
      var saved = localStorage.getItem( 'theme' ) || 'light';
      document.documentElement.setAttribute( 'data-theme', saved );
      var isDark = saved === 'dark';
      document.getElementById( 'sunIcon' ).style.display = isDark ? 'none' : 'block';
      document.getElementById( 'moonIcon' ).style.display = isDark ? 'block' : 'none';
      document.getElementById( 'themeLabel' ).textContent = isDark ? 'Light' : 'Dark';
    } )();

    document.getElementById( 'themeToggle' ).addEventListener( 'click', function () {
      var html = document.documentElement;
      var isDark = html.getAttribute( 'data-theme' ) === 'dark';
      var next = isDark ? 'light' : 'dark';
      html.setAttribute( 'data-theme', next );
      localStorage.setItem( 'theme', next );
      document.getElementById( 'sunIcon' ).style.display = isDark ? 'block' : 'none';
      document.getElementById( 'moonIcon' ).style.display = isDark ? 'none' : 'block';
      document.getElementById( 'themeLabel' ).textContent = isDark ? 'Dark' : 'Light';
    } );

    /* ── Price stepper function ── */
    function stepPrice( delta ) {
      var el = document.getElementById( 'max_price' );
      var val = parseFloat( el.value ) || 0;
      var next = Math.max( 0, val + delta );
      el.value = next > 0 ? next : '';
      el.focus();
    }


    /* ── CSV Export (client-side) ── */
    ( function () {
      var btn = document.getElementById( 'exportCsvBtn' );
      if ( !btn ) return;
      btn.addEventListener( 'click', function () {
        var table = document.getElementById( 'resultsTable' );
        if ( !table ) return;
        var csv = [];
        var rows = table.querySelectorAll( 'tr' );
        rows.forEach( function ( row ) {
          var cols = row.querySelectorAll( 'th, td' );
          var rowData = [];
          cols.forEach( function ( col ) {
            var text = col.innerText.replace( /[\n\r]/g, ' ' ).trim();
            // Remove sort arrows from header text
            text = text.replace( /[▲▼]/g, '' ).trim();
            // Escape quotes per RFC 4180
            if ( text.indexOf( ',' ) !== -1 || text.indexOf( '"' ) !== -1 )
            {
              text = '"' + text.replace( /"/g, '""' ) + '"';
            }
            rowData.push( text );
          } );
          csv.push( rowData.join( ',' ) );
        } );
        var blob = new Blob( [ csv.join( '\n' ) ], { type: 'text/csv;charset=utf-8;' } );
        var url = URL.createObjectURL( blob );
        var a = document.createElement( 'a' );
        var date = new Date().toISOString().slice( 0, 10 );
        a.href = url;
        a.download = 'instruments_' + date + '.csv';
        document.body.appendChild( a );
        a.click();
        document.body.removeChild( a );
        URL.revokeObjectURL( url );
      } );
    } )();

    /* ── Print button ── */
    ( function () {
      var btn = document.getElementById( 'printBtn' );
      if ( !btn ) return;
      btn.addEventListener( 'click', function () {
        window.print();
      } );
    } )();

    /* ── Keyboard shortcuts ── */
    document.addEventListener( 'keydown', function ( e ) {
      // Ctrl/Cmd + K: focus server IP input
      if ( ( e.ctrlKey || e.metaKey ) && e.key === 'k' )
      {
        e.preventDefault();
        var ip = document.getElementById( 'server_ip' );
        if ( ip ) { ip.focus(); ip.select(); }
      }
      // Escape: clear all form fields (only when not in an input)
      if ( e.key === 'Escape' )
      {
        var tag = document.activeElement.tagName.toLowerCase();
        if ( tag === 'input' || tag === 'select' )
        {
          document.activeElement.blur();
        } else
        {
          var form = document.querySelector( '.search-card form' );
          if ( form )
          {
            form.reset();
          }
        }
      }
    } );

    /* ── Table column sorting ── */
    ( function () {
      var table = document.getElementById( 'resultsTable' ); if ( !table ) return;
      var headers = table.querySelectorAll( 'thead th[data-col]' );
      var tbody = table.querySelector( 'tbody' );
      var sortCol = 3, sortDir = 'asc';
      headers.forEach( function ( th ) {
        th.addEventListener( 'click', function () {
          var col = parseInt( th.getAttribute( 'data-col' ) );
          var type = th.getAttribute( 'data-type' );
          if ( sortCol === col )
          {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
          } else
          {
            sortCol = col;
            sortDir = 'asc';
          }
          headers.forEach( function ( h ) {
            h.classList.remove( 'sorted' );
            h.querySelector( '.sort-icon' ).innerHTML = '&#9650;';
          } );
          th.classList.add( 'sorted' );
          th.querySelector( '.sort-icon' ).innerHTML = sortDir === 'asc' ? '&#9650;' : '&#9660;';
          var rows = Array.from( tbody.querySelectorAll( 'tr' ) );
          rows.sort( function ( a, b ) {
            var aVal = a.children[ col ].textContent.trim();
            var bVal = b.children[ col ].textContent.trim();
            if ( type === 'num' )
            {
              aVal = parseFloat( aVal.replace( /[$,]/g, '' ) ) || 0;
              bVal = parseFloat( bVal.replace( /[$,]/g, '' ) ) || 0;
            } else
            {
              aVal = aVal.toLowerCase();
              bVal = bVal.toLowerCase();
            }
            if ( aVal < bVal ) return sortDir === 'asc' ? -1 : 1;
            if ( aVal > bVal ) return sortDir === 'asc' ? 1 : -1;
            return 0;
          } );
          rows.forEach( function ( row ) { tbody.appendChild( row ); } );
        } );
      } );
    } )();

    /* ── Network scanner ── */
    ( function () {
      var scanBtn = document.getElementById( 'scanBtn' );
      if ( !scanBtn ) return;
      scanBtn.addEventListener( 'click', function () {
        var uidFrom = parseInt( document.getElementById( 'uidFrom' ).value ) || 1;
        var uidTo = parseInt( document.getElementById( 'uidTo' ).value ) || 120;
        if ( uidFrom > uidTo ) { var t = uidFrom; uidFrom = uidTo; uidTo = t; }

        scanBtn.disabled = true;
        var status = document.getElementById( 'scanStatus' );
        var statusText = document.getElementById( 'scanStatusText' );
        status.style.display = 'flex';
        statusText.textContent = 'Scanning UIDs ' + uidFrom + '–' + uidTo + '…';

        var container = document.getElementById( 'discoveredContainer' );
        var tagsEl = document.getElementById( 'serverTags' );

        fetch( '?action=scan&uid_from=' + uidFrom + '&uid_to=' + uidTo )
          .then( function ( r ) { return r.json(); } )
          .then( function ( data ) {
            scanBtn.disabled = false;
            status.style.display = 'none';
            tagsEl.innerHTML = '';

            if ( data.servers.length === 0 )
            {
              container.style.display = 'block';
              tagsEl.innerHTML = '<div class="scan-empty">No servers found in the scanned range. ' +
                'Make sure you are on the classroom network.</div>';
              return;
            }

            container.style.display = 'block';
            data.servers.forEach( function ( srv ) {
              var btn = document.createElement( 'button' );
              btn.type = 'button';
              btn.className = 'server-tag';
              btn.innerHTML = srv.ip +
                ' <span class="tag-uid">UID ' + srv.uid + '</span>' +
                ( srv.is_you ? ' <span class="tag-you">You</span>' : '' );
              btn.addEventListener( 'click', function () {
                document.getElementById( 'server_ip' ).value = srv.ip;
                // Brief highlight
                var ipInput = document.getElementById( 'server_ip' );
                ipInput.style.borderColor = 'var(--accent)';
                ipInput.style.boxShadow = '0 0 0 3px rgba(196,167,125,.25)';
                setTimeout( function () {
                  ipInput.style.borderColor = '';
                  ipInput.style.boxShadow = '';
                }, 800 );
              } );
              tagsEl.appendChild( btn );
            } );

            statusText.textContent = data.servers.length + ' server(s) found out of ' + data.scanned + ' scanned';
            status.style.display = 'flex';
            // Hide spinner, just show text
            status.querySelector( '.scan-spinner' ).style.display = 'none';
          } )
          .catch( function () {
            scanBtn.disabled = false;
            status.style.display = 'none';
            tagsEl.innerHTML = '<div class="scan-empty">Scan failed — check your connection.</div>';
            container.style.display = 'block';
          } );
      } );
    } )();
  </script>
</body>

</html>
