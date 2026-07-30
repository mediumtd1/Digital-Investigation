<?php
// includes/functions.php

/**
 * Generate CSRF token and return it
 */
// CSRF functions moved to config/config.php for global availability

/**
 * Send OTP via Gmail SMTP (raw SMTP, no library needed)
 * Returns true on success, false on failure
 */
function send_otp_email($to_email, $otp, $subject = 'Registration OTP') {
    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT; 
    $smtp_user = SMTP_USER;
    $smtp_pass = SMTP_PASS;
    $from_name = 'Digital Investigation Portal';

    $body    = "Greetings,\r\n\r\n"
             . "Your One-Time Password (OTP) for verification is:\r\n\r\n"
             . "    $otp\r\n\r\n"
             . "This OTP is valid for 10 minutes only. Please do not share this code with anyone.\r\n\r\n"
             . "Regards,\r\nDigital Investigation Portal";

    $smtp_read = function($sock) {
        $resp = '';
        while ($line = fgets($sock, 512)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($resp);
    };

    // PROTOCOL A: SSL on 465
    $context = stream_context_create(['ssl' => [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'allow_self_signed' => false,
    ]]);
    $sock = @stream_socket_client("ssl://$smtp_host:465", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

    // PROTOCOL B: STARTTLS on 587 Fallback
    if (!$sock) {
        $sock = @stream_socket_client("tcp://$smtp_host:587", $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
        if ($sock) {
            $smtp_read($sock);
            fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $smtp_read($sock);
            fwrite($sock, "STARTTLS\r\n");
            $smtp_read($sock);
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
    }

    // Fallback to PHP mail() if SMTP fails
    if (!$sock) {
        $headers = "From: $from_name <$smtp_user>\r\n";
        $headers .= "Reply-To: $smtp_user\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        return mail($to_email, $subject, $body, $headers);
    }

    $smtp_read($sock);
    fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
    $smtp_read($sock);
    fwrite($sock, "AUTH LOGIN\r\n");
    $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_user) . "\r\n");
    $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_pass) . "\r\n");
    $auth = $smtp_read($sock);

    if (strpos($auth, '235') === false) {
        // Auth failed, try mail() fallback as last resort
        fclose($sock);
        $headers = "From: $from_name <$smtp_user>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return mail($to_email, $subject, $body, $headers);
    }

    fwrite($sock, "MAIL FROM:<$smtp_user>\r\n");
    $smtp_read($sock);
    fwrite($sock, "RCPT TO:<$to_email>\r\n");
    $smtp_read($sock);
    fwrite($sock, "DATA\r\n");
    $smtp_read($sock);

    $headers = "From: $from_name <$smtp_user>\r\nTo: $to_email\r\nSubject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    fwrite($sock, "$headers\r\n$body\r\n.\r\n");
    $res = $smtp_read($sock);
    fwrite($sock, "QUIT\r\n");
    fclose($sock);

    return (strpos($res, '250') !== false);
}

/**
 * Sanitize html output
 */
if (!function_exists('esc')) {
    function esc($string)
    {
        if ($string === null)
            return '';
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Generate random OTP
 */
function generate_otp($length = 6)
{
    $otp = "";
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

/**
 * Audit Logger
 */
function log_audit($pdo, $user_id, $role, $action)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_mapped_id, user_role, action, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $role, $action, $ip]);
    } catch (Exception $e) {
        // Fail silently if audit table is corrupted or missing
    }
}

/**
 * Check Authentication and Role
 */
function check_auth($role)
{
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        // No-cache headers — browser back button ke baad cached page na dikhao
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
        // Use relative path for maximum reliability on local servers
        header("Location: login.php");
        exit;
    }
}

/**
 * Asynchronous background email sender (supports optional file attachment)
 * $attachment_path: absolute path to the file to attach (optional)
 */
function send_custom_email($to_email, $subject, $body, $attachment_path = null) {
    return send_custom_email_with_attachment_sync($to_email, $subject, $body, $attachment_path);
}

/**
 * Send custom notification email via secure SMTP with standard PHP mail() fallback (Synchronous)
 */
function send_custom_email_sync($to_email, $subject, $body) {
    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT; 
    $smtp_user = SMTP_USER;
    $smtp_pass = SMTP_PASS;
    $from_name = 'Digital Investigation Portal';

    $smtp_read = function($sock) {
        $resp = '';
        while ($line = fgets($sock, 512)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($resp);
    };

    // PROTOCOL A: SSL on 465
    $context = stream_context_create(['ssl' => [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'allow_self_signed' => false,
    ]]);
    $sock = @stream_socket_client("ssl://$smtp_host:465", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

    // PROTOCOL B: STARTTLS on 587 Fallback
    if (!$sock) {
        $sock = @stream_socket_client("tcp://$smtp_host:587", $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
        if ($sock) {
            $smtp_read($sock);
            fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $smtp_read($sock);
            fwrite($sock, "STARTTLS\r\n");
            $smtp_read($sock);
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
    }

    // Fallback to PHP mail() if SMTP fails
    if (!$sock) {
        $headers = "From: $from_name <$smtp_user>\r\n";
        $headers .= "Reply-To: $smtp_user\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        return mail($to_email, $subject, $body, $headers);
    }

    $smtp_read($sock);
    fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
    $smtp_read($sock);
    fwrite($sock, "AUTH LOGIN\r\n");
    $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_user) . "\r\n");
    $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_pass) . "\r\n");
    $auth = $smtp_read($sock);

    if (strpos($auth, '235') === false) {
        // Auth failed, try mail() fallback as last resort
        fclose($sock);
        $headers = "From: $from_name <$smtp_user>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return mail($to_email, $subject, $body, $headers);
    }

    fwrite($sock, "MAIL FROM:<$smtp_user>\r\n");
    $smtp_read($sock);
    fwrite($sock, "RCPT TO:<$to_email>\r\n");
    $smtp_read($sock);
    fwrite($sock, "DATA\r\n");
    $smtp_read($sock);

    $headers = "From: $from_name <$smtp_user>\r\nTo: $to_email\r\nSubject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    fwrite($sock, "$headers\r\n$body\r\n.\r\n");
    $res = $smtp_read($sock);
    fwrite($sock, "QUIT\r\n");
    fclose($sock);

    return (strpos($res, '250') !== false);
}

/**
 * Send email with MIME multipart attachment via SMTP (Synchronous)
 * $attachment_path: absolute filesystem path to the file to attach (optional)
 */
function send_custom_email_with_attachment_sync($to_email, $subject, $body, $attachment_path = null) {
    // If no valid attachment, fall back to simple sync sender
    if (empty($attachment_path) || !file_exists($attachment_path)) {
        return send_custom_email_sync($to_email, $subject, $body);
    }

    $smtp_host = SMTP_HOST;
    $smtp_user = SMTP_USER;
    $smtp_pass = SMTP_PASS;
    $from_name = 'Digital Investigation Portal';

    // Build MIME multipart body
    $boundary  = '----=_Part_' . md5(uniqid(mt_rand(), true));
    $file_name = basename($attachment_path);
    $file_data = base64_encode(file_get_contents($attachment_path));
    $mime_type = mime_content_type($attachment_path) ?: 'application/octet-stream';

    $mime_body  = "--{$boundary}\r\n";
    $mime_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mime_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $mime_body .= $body . "\r\n\r\n";
    $mime_body .= "--{$boundary}\r\n";
    $mime_body .= "Content-Type: {$mime_type}; name=\"{$file_name}\"\r\n";
    $mime_body .= "Content-Transfer-Encoding: base64\r\n";
    $mime_body .= "Content-Disposition: attachment; filename=\"{$file_name}\"\r\n\r\n";
    $mime_body .= chunk_split($file_data) . "\r\n";
    $mime_body .= "--{$boundary}--";

    $full_headers = "From: {$from_name} <{$smtp_user}>\r\n"
                  . "To: {$to_email}\r\n"
                  . "Subject: {$subject}\r\n"
                  . "MIME-Version: 1.0\r\n"
                  . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $smtp_read = function($sock) {
        $resp = '';
        while ($line = fgets($sock, 512)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($resp);
    };

    // Try SSL on 465
    $context = stream_context_create(['ssl' => [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'allow_self_signed' => false,
    ]]);
    $sock = @stream_socket_client("ssl://{$smtp_host}:465", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

    // Fallback: STARTTLS on 587
    if (!$sock) {
        $sock = @stream_socket_client("tcp://{$smtp_host}:587", $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
        if ($sock) {
            $smtp_read($sock);
            fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n"); $smtp_read($sock);
            fwrite($sock, "STARTTLS\r\n"); $smtp_read($sock);
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
    }

    // Final fallback: PHP mail()
    if (!$sock) {
        return mail($to_email, $subject, $mime_body, $full_headers);
    }

    $smtp_read($sock);
    fwrite($sock, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n"); $smtp_read($sock);
    fwrite($sock, "AUTH LOGIN\r\n"); $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_user) . "\r\n"); $smtp_read($sock);
    fwrite($sock, base64_encode($smtp_pass) . "\r\n");
    $auth = $smtp_read($sock);

    if (strpos($auth, '235') === false) {
        fclose($sock);
        return mail($to_email, $subject, $mime_body, $full_headers);
    }

    fwrite($sock, "MAIL FROM:<{$smtp_user}>\r\n"); $smtp_read($sock);
    fwrite($sock, "RCPT TO:<{$to_email}>\r\n");    $smtp_read($sock);
    fwrite($sock, "DATA\r\n");                      $smtp_read($sock);
    fwrite($sock, "{$full_headers}\r\n{$mime_body}\r\n.\r\n");
    $res = $smtp_read($sock);
    fwrite($sock, "QUIT\r\n");
    fclose($sock);

    return (strpos($res, '250') !== false);
}
