<?php
header("Content-Type: application/json; charset=utf-8");

require __DIR__ . '/env.php';
loadEnv(__DIR__ . '/.env');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Metoda neacceptata"]);
    exit;
}

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$chatId   = $_ENV['TELEGRAM_CHAT_ID'] ?? '';

if ($botToken === '' || $chatId === '') {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Configurare Telegram lipsa in .env"]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Date invalide"]);
    exit;
}

session_start();
$now = time();
if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 20) {
    http_response_code(429);
    echo json_encode(["ok" => false, "error" => "Te rugam asteapta putin"]);
    exit;
}

function clean_field($value, $maxLen = 500) {
    $value = is_string($value) ? trim($value) : '';
    $value = strip_tags($value);
    return mb_substr($value, 0, $maxLen);
}

$nume       = clean_field($data['nume'] ?? '', 100);
$telefon    = clean_field($data['telefon'] ?? '', 40);
$localitate = clean_field($data['localitate'] ?? '', 50);
$serviciu   = clean_field($data['serviciu'] ?? '', 50);
$mesaj      = clean_field($data['mesaj'] ?? '', 1000);

if ($nume === '' || $telefon === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Nume si telefon sunt obligatorii"]);
    exit;
}
if (!preg_match('/^[0-9+\s()\-]{6,20}$/', $telefon)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Telefon invalid"]);
    exit;
}

$localitatiLabel = [
    'Soroca' => 'Soroca', 'Floresti' => 'Floresti', 'Drochia' => 'Drochia',
    'Balti' => 'Balti', 'Alta' => 'Alta localitate',
];
$serviciiLabel = [
    'Constructie case' => 'Constructie case', 'Reparatii' => 'Reparatii',
    'Renovari' => 'Renovari', 'Mesteri' => 'Mesteri',
    'Acoperisuri' => 'Acoperisuri', 'Fatade' => 'Fatade', 'Altceva' => 'Altceva',
];

$localitateText = $localitatiLabel[$localitate] ?? $localitate;
$serviciuText   = $serviciiLabel[$serviciu] ?? $serviciu;

function tgEscape($value) {
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
}

$divider = "━━━━━━━━━━━━━━━";
$lines = [
    "🏗 <b>Cerere nouă</b> — <i>TopCons.md</i>",
    $divider,
    "👤 <b>Nume:</b> " . tgEscape($nume),
    "📞 <b>Telefon:</b> <code>" . tgEscape($telefon) . "</code>",
    "📍 <b>Localitate:</b> " . tgEscape($localitateText !== '' ? $localitateText : '-'),
    "🛠 <b>Serviciu:</b> " . tgEscape($serviciuText !== '' ? $serviciuText : '-'),
];
if ($mesaj !== '') { $lines[] = "📝 <b>Detalii:</b> " . tgEscape($mesaj); }
$lines[] = $divider;
$lines[] = "🕒 " . date('d.m.Y, H:i');
$text = implode("\n", $lines);

$telegramUrl = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
$ch = curl_init($telegramUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POSTFIELDS => http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
    ]),
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError || $httpCode >= 300) {
    error_log("TopCons trimite.php - eroare Telegram: " . $curlError . " | HTTP " . $httpCode . " | " . $response);
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => "Nu am putut trimite mesajul, incearca din nou"]);
    exit;
}

$_SESSION['last_submit'] = $now;
echo json_encode(["ok" => true]);
