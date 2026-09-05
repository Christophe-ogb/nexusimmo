<?php

class EmailVerification
{
    public static function send(string $email, int $userId, string $token): void
    {
        if (BREVO_API_KEY === '') {
            throw new RuntimeException('La clé API Brevo n’est pas configurée.');
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('L’extension PHP cURL est requise pour envoyer les e-mails.');
        }

        $verificationUrl = APP_URL . '/verify-email.php?id=' . $userId . '&token=' . urlencode($token);
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');
        $subject = 'Vérifie ton adresse e-mail - ' . APP_NAME;
        $payload = [
            'sender' => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM_ADDRESS],
            'replyTo' => ['email' => MAIL_FROM_ADDRESS],
            'to' => [['email' => $email]],
            'subject' => $subject,
            'htmlContent' => '<!doctype html><html><body><p>Bonjour,</p><p>Pour activer ton compte ' . APP_NAME . ', clique sur le bouton ci-dessous.</p><p><a href="' . $safeUrl . '">Activer mon compte</a></p><p>Ce lien expire dans 24 heures. Si tu n’as pas créé de compte, ignore cet e-mail.</p></body></html>',
            'tags' => ['email-verification'],
        ];

        $curl = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . BREVO_API_KEY,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('Impossible de joindre Brevo : ' . $curlError);
        }

        if ($status !== 201) {
            throw new RuntimeException('Brevo a refusé l’e-mail (HTTP ' . $status . ') : ' . substr($body, 0, 500));
        }
    }
}
