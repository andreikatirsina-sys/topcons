<?php
/**
 * bot.php — TopCons.md
 * Bot Telegram cu meniu, comenzi si poza de bun venit.
 * Fara baza de date - cererile trimise din chat ajung direct la tine pe Telegram.
 *
 * Rulare (din PowerShell, in folderul site-ului):
 *   php bot.php
 * Lasa fereastra deschisa cat timp vrei ca botul sa raspunda.
 */

require __DIR__ . '/env.php';
loadEnv(__DIR__ . '/.env');

$BOT_TOKEN = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$CHAT_ID   = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
$SITE_URL  = $_ENV['SITE_URL'] ?? '';
$PHONE     = $_ENV['CONTACT_PHONE'] ?? '';
$EMAIL     = $_ENV['CONTACT_EMAIL'] ?? '';

if ($BOT_TOKEN === '' || $CHAT_ID === '') {
    die("Lipseste TELEGRAM_BOT_TOKEN sau TELEGRAM_CHAT_ID din .env\n");
}

$API = "https://api.telegram.org/bot" . $BOT_TOKEN . "/";

function tg($method, $params = []) {
    global $API;
    $ch = curl_init($API . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 35);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Trimite o poza locala (multipart), separat de tg() care e doar JSON
function tgSendPhoto($chatId, $photoPath, $caption, $keyboard = null) {
    global $API;
    if (!file_exists($photoPath)) {
        // Daca poza nu exista inca (nu ai rulat download-images.ps1), trimite doar text
        tg('sendMessage', [
            'chat_id' => $chatId,
            'text' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard,
        ]);
        return;
    }
    $ch = curl_init($API . 'sendPhoto');
    $post = [
        'chat_id' => $chatId,
        'caption' => $caption,
        'parse_mode' => 'HTML',
        'photo' => new CURLFile($photoPath),
    ];
    if ($keyboard) {
        $post['reply_markup'] = json_encode($keyboard);
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 35);
    curl_exec($ch);
    curl_close($ch);
}

function menuPrincipal() {
    return [
        'inline_keyboard' => [
            [['text' => '🛠 Serviciile noastre', 'callback_data' => 'servicii']],
            [['text' => '📞 Contact', 'callback_data' => 'contact']],
            [['text' => '📝 Trimite o cerere', 'callback_data' => 'cerere_start']],
        ]
    ];
}

function divider() { return "━━━━━━━━━━━━━━━"; }
function tgEscape($v) { return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $v); }

function trimiteServicii($chatId) {
    tg('sendMessage', [
        'chat_id' => $chatId,
        'text' => "🛠 <b>Serviciile noastre</b>\n" . divider() . "\n\n"
                . "🏠 Construcție case\n"
                . "🔧 Reparații\n"
                . "🎨 Renovări\n"
                . "👷 Meșteri\n"
                . "🏚 Acoperișuri\n"
                . "🧱 Fațade\n\n"
                . "Scrie /cerere ca sa trimiti o solicitare, sau /start pentru meniu.",
        'parse_mode' => 'HTML',
    ]);
}

function trimiteContact($chatId) {
    global $PHONE, $EMAIL, $SITE_URL;
    $lines = ["📞 <b>Contact TopCons.md</b>", divider(), ""];
    if ($PHONE)  $lines[] = "☎️ Telefon: <code>{$PHONE}</code>";
    if ($EMAIL)  $lines[] = "✉️ Email: {$EMAIL}";
    if ($SITE_URL) $lines[] = "🌐 Site: {$SITE_URL}";
    $lines[] = "📍 Zonă: Soroca, Florești, Drochia, Bălți";
    $lines[] = "";
    $lines[] = "Scrie /start pentru meniu.";
    tg('sendMessage', [
        'chat_id' => $chatId,
        'text' => implode("\n", $lines),
        'parse_mode' => 'HTML',
    ]);
}

// --- Inregistram comenzile care apar ca sugestii in Telegram ---
tg('setMyCommands', [
    'commands' => [
        ['command' => 'start', 'description' => 'Deschide meniul principal'],
        ['command' => 'servicii', 'description' => 'Vezi ce servicii oferim'],
        ['command' => 'contact', 'description' => 'Telefon, email, zona deservita'],
        ['command' => 'cerere', 'description' => 'Trimite o cerere rapida'],
    ]
]);

echo "Bot pornit. Astept mesaje...\n";

$offset = 0;
$stari = []; // stare conversatie per chat_id, pastrata cat timp scriptul ruleaza

while (true) {
    $updates = tg('getUpdates', ['offset' => $offset, 'timeout' => 30]);

    if (!empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $offset = $update['update_id'] + 1;

            // --- Click pe buton ---------------------------------
            if (isset($update['callback_query'])) {
                $cq = $update['callback_query'];
                $chatId = $cq['message']['chat']['id'];
                $data = $cq['data'];

                tg('answerCallbackQuery', ['callback_query_id' => $cq['id']]);

                if ($data === 'servicii') {
                    trimiteServicii($chatId);
                } elseif ($data === 'contact') {
                    trimiteContact($chatId);
                } elseif ($data === 'cerere_start') {
                    $stari[$chatId] = ['pas' => 'nume', 'date' => []];
                    tg('sendMessage', ['chat_id' => $chatId, 'text' => "Hai să completăm o cerere rapidă.\n\nCum te numești?"]);
                }
                continue;
            }

            // --- Mesaj text --------------------------------------
            if (isset($update['message']['text'])) {
                $msg = $update['message'];
                $chatId = $msg['chat']['id'];
                $text = trim($msg['text']);
                $from = $msg['from'];

                if ($text === '/start') {
                    unset($stari[$chatId]);
                    $numeUser = $from['first_name'] ?? '';
                    tgSendPhoto(
                        $chatId,
                        __DIR__ . '/assets/hero.jpg',
                        "👋 Salut" . ($numeUser ? ", {$numeUser}" : "") . "! Bine ai venit la <b>TopCons.md</b>.\n\n"
                        . "Facem construcții, reparații și renovări în nordul Moldovei.\n"
                        . "Alege o opțiune mai jos 👇",
                        menuPrincipal()
                    );
                    continue;
                }

                if ($text === '/servicii') { trimiteServicii($chatId); continue; }
                if ($text === '/contact')  { trimiteContact($chatId); continue; }
                if ($text === '/cerere') {
                    $stari[$chatId] = ['pas' => 'nume', 'date' => []];
                    tg('sendMessage', ['chat_id' => $chatId, 'text' => "Hai să completăm o cerere rapidă.\n\nCum te numești?"]);
                    continue;
                }

                // --- Conversatie activa (cerere in curs) ----------
                if (isset($stari[$chatId])) {
                    $stare = &$stari[$chatId];

                    if ($stare['pas'] === 'nume') {
                        $stare['date']['nume'] = $text;
                        $stare['pas'] = 'telefon';
                        tg('sendMessage', ['chat_id' => $chatId, 'text' => "Mulțumesc. Numărul tău de telefon?"]);

                    } elseif ($stare['pas'] === 'telefon') {
                        $stare['date']['telefon'] = $text;
                        $stare['pas'] = 'localitate';
                        tg('sendMessage', ['chat_id' => $chatId, 'text' => "În ce localitate e lucrarea?"]);

                    } elseif ($stare['pas'] === 'localitate') {
                        $stare['date']['localitate'] = $text;
                        $stare['pas'] = 'mesaj';
                        tg('sendMessage', ['chat_id' => $chatId, 'text' => "Ultimul pas: descrie pe scurt lucrarea (sau scrie '-')."]);

                    } elseif ($stare['pas'] === 'mesaj') {
                        $stare['date']['mesaj'] = $text;
                        $d = $stare['date'];

                        tg('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => "✅ Gata! Am înregistrat cererea ta. Te contactăm în curând.\n\nScrie /start pentru meniu.",
                        ]);

                        $lines = [
                            "🔔 <b>Cerere nouă din bot</b>", divider(),
                            "👤 <b>Nume:</b> " . tgEscape($d['nume']),
                            "📞 <b>Telefon:</b> <code>" . tgEscape($d['telefon']) . "</code>",
                            "📍 <b>Localitate:</b> " . tgEscape($d['localitate']),
                            "📝 <b>Detalii:</b> " . tgEscape($d['mesaj']),
                            divider(),
                            "🕒 " . date('d.m.Y, H:i'),
                        ];
                        tg('sendMessage', [
                            'chat_id' => $CHAT_ID,
                            'text' => implode("\n", $lines),
                            'parse_mode' => 'HTML',
                        ]);

                        unset($stari[$chatId]);
                    }
                    continue;
                }

                // --- Orice alt mesaj ------------------------------
                tg('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "Nu am înțeles mesajul. Scrie /start pentru meniu.",
                ]);
            }
        }
    }
}
