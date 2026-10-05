<?php

/**
 * Outgoing email. Messages from the contact and Work With Us forms are sent
 * through Resend (https://resend.com) from an address on SCA's own domain, so
 * they arrive reliably in SCA's inbox; the visitor's address is the Reply-To,
 * so answering the email answers the visitor.
 *
 * Settings (app/config.local.php, server only):
 *   'resend_api_key' => 're_…'
 *   'mail_from'      => 'Saiga Conservation Alliance website <website@saiga-conservation.org>'
 * Without an API key the server's own mail() is tried instead.
 */

/**
 * Sends one email. Returns '' on success, otherwise a short reason that is
 * stored with the message in the admin.
 */
function send_email(array $to, string $subject, string $text, string $html, string $replyTo = ''): string
{
    $to = array_values(array_filter(array_map('trim', $to), fn ($a) => filter_var($a, FILTER_VALIDATE_EMAIL)));
    if (!$to) {
        return 'No valid recipient address is set.';
    }
    $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject));
    $replyTo = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : '';
    $from = trim((string) config('mail_from', '')) ?: 'Saiga Conservation Alliance website <website@saiga-conservation.org>';

    $key = trim((string) config('resend_api_key', ''));
    if ($key !== '' && function_exists('curl_init')) {
        $payload = ['from' => $from, 'to' => $to, 'subject' => $subject, 'text' => $text, 'html' => $html];
        if ($replyTo !== '') {
            $payload['reply_to'] = [$replyTo];
        }
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json', 'User-Agent: sca-website'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($status >= 200 && $status < 300) {
            return '';
        }
        $reason = $error !== '' ? $error : (string) (json_decode((string) $body, true)['message'] ?? ('HTTP ' . $status));
        error_log('Resend: ' . $status . ' ' . $reason);
        return mb_substr('Email not sent (' . $reason . ')', 0, 250);
    }

    // Fallback: the web server's own mail().
    $headers = "From: $from\r\nContent-Type: text/plain; charset=utf-8" . ($replyTo !== '' ? "\r\nReply-To: $replyTo" : '');
    return @mail(implode(', ', $to), '=?UTF-8?B?' . base64_encode($subject) . '?=', $text, $headers)
        ? '' : 'Email not sent (the server could not send it).';
}

/** Where new contact messages go: the admin setting, else the server config. */
function notify_recipients(): array
{
    $list = trim((string) site('contactForm.notifyEmail')) ?: trim((string) config('notify_email', ''));
    return array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $list))));
}

/** Emails a new contact / Work With Us message to SCA. Returns '' or the reason it failed. */
function send_contact_notification(string $name, string $email, string $message, string $source): string
{
    $form = $source === 'work-with-us' ? 'Work With Us' : ($source === 'contact' ? 'Contact Us' : $source);
    $subject = 'New message from the website (' . $form . ')' . ($name !== '' ? ' — ' . $name : '');
    $text = "Name: $name\nEmail: $email\nForm: $form\n\n$message\n\n—\nReply to this email to answer $name directly. All messages are also kept under Admin → Messages.";
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#2e231a;max-width:640px">'
        . '<p style="margin:0 0 16px;color:#8a7458;font-size:13px;text-transform:uppercase;letter-spacing:.08em">New message · ' . e($form) . ' form</p>'
        . '<table style="border-collapse:collapse;margin-bottom:20px">'
        . '<tr><td style="padding:4px 16px 4px 0;color:#8a7458">Name</td><td style="padding:4px 0"><strong>' . e($name) . '</strong></td></tr>'
        . '<tr><td style="padding:4px 16px 4px 0;color:#8a7458">Email</td><td style="padding:4px 0"><a href="mailto:' . e($email) . '" style="color:#a3602b">' . e($email) . '</a></td></tr>'
        . '</table>'
        . '<div style="white-space:pre-wrap;border-left:3px solid #c87a3c;padding:4px 0 4px 16px">' . e($message) . '</div>'
        . '<p style="margin:24px 0 0;color:#8a7458;font-size:13px">Reply to this email to answer ' . e($name ?: 'the sender') . ' directly. All messages are also kept under Admin → Messages on the website.</p>'
        . '</div>';
    return send_email(notify_recipients(), $subject, $text, $html, $email);
}
