<?php
try {
    require_once __DIR__ . '/../../../config/db_connect.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de réunion invalide.');
    }
    $reunion_id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM reunions WHERE id = ?");
    $stmt->execute([$reunion_id]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        throw new Exception('Réunion non trouvée.');
    }

    function escape_ics_text($text) {
        return str_replace([',', ';', '\\'], ['\\,', '\\;', '\\\\'], $text);
    }

    $summary = escape_ics_text($reunion['titre']);
    $description = escape_ics_text($reunion['description']);
    $location = escape_ics_text($reunion['lieu']);
    $uid = 'reunion-' . $reunion['id'] . '@' . ($_SERVER['HTTP_HOST'] ?? 'checkmaster.com');
    $filename = 'reunion-' . preg_replace('/[^a-z0-9]+/i', '-', $reunion['titre']) . '.ics';

    $tz = date_default_timezone_get();
    $dtstart = new DateTime($reunion['date_reunion'] . ' ' . $reunion['heure_debut'], new DateTimeZone($tz));
    $dtend = clone $dtstart;
    $dtend->modify('+' . ((float)$reunion['duree'] * 3600) . ' seconds');
    $dtstamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//CheckMaster//GSCV-Axel//FR\r\n";
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . $uid . "\r\n";
    echo "DTSTAMP:" . $dtstamp . "\r\n";
    echo "DTSTART;TZID=" . $tz . ":" . $dtstart->format('Ymd\THis') . "\r\n";
    echo "DTEND;TZID=" . $tz . ":" . $dtend->format('Ymd\THis') . "\r\n";
    echo "SUMMARY:" . $summary . "\r\n";
    echo "DESCRIPTION:" . $description . "\r\n";
    echo "LOCATION:" . $location . "\r\n";
    echo "END:VEVENT\r\n";
    echo "END:VCALENDAR\r\n";
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die('Erreur : ' . $e->getMessage());
}
?> 