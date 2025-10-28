<?php
    include('dbConnection.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/mail-config.php';

    // ... existing functions stay intact ...

    // Convert a YouTube URL (watch or youtu.be) into an embeddable URL
    function toYouTubeEmbedUrl($url) {
        if (!$url) return '';
        $url = trim($url);
        // youtu.be/VIDEO_ID
        if (preg_match('#https?://youtu\\.be/([A-Za-z0-9_-]{11})#', $url, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }
        // youtube.com/watch?v=VIDEO_ID (keep optional start time)
        if (preg_match('#https?://(www\\.)?youtube\\.com/watch\\?([^ ]+)#', $url)) {
            $query = parse_url($url, PHP_URL_QUERY) ?? '';
            parse_str($query, $q);
            if (!empty($q['v']) && preg_match('#^[A-Za-z0-9_-]{11}$#', $q['v'])) {
                $id = $q['v'];
                $start = 0;
                if (!empty($q['t'])) {
                    $t = $q['t'];
                    if (preg_match('#^(\\d+)s?$#', $t, $sm)) $start = (int)$sm[1];
                    elseif (preg_match('#^(\\d+)m(\\d+)s?$#', $t, $sm)) $start = (int)$sm[1]*60 + (int)$sm[2];
                } elseif (!empty($q['start'])) {
                    $start = (int)$q['start'];
                }
                return "https://www.youtube.com/embed/{$id}" . ($start > 0 ? "?start={$start}" : "");
            }
        }
        // Already an embed URL
        if (preg_match('#https?://(www\\.)?youtube\\.com/embed/([A-Za-z0-9_-]{11})#', $url)) {
            return $url;
        }
        return '';
    }
?>