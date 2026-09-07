<?php
/**
 * config.example.php — TopCons.md
 *
 * COPIAZĂ acest fișier ca "config.php" pe server și completează valorile reale.
 * NU urca "config.php" într-un repo public / git — conține date secrete.
 *
 * Cum obții aceste valori:
 * 1. Deschide Telegram, caută @BotFather și creează un bot nou cu /newbot.
 *    Vei primi un token de forma: 123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 * 2. Adaugă botul într-un grup/canal, apoi află CHAT_ID-ul
 *    (ex: cu @userinfobot, sau prin metoda getUpdates a Bot API-ului
 *    după ce ai trimis un mesaj către bot).
 * 3. Completează valorile mai jos și salvează fișierul ca config.php,
 *    în același folder cu trimite.php, pe server.
 */

define('TELEGRAM_BOT_TOKEN', 'PUNE_AICI_TOKENUL_BOTULUI');
define('TELEGRAM_CHAT_ID', 'PUNE_AICI_CHAT_ID_UL');
