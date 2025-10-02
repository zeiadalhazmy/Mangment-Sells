<?php
// config/email.php
return [
    'host'       => getenv('SMTP_HOST') ?: 'localhost',
    'port'       => (int)(getenv('SMTP_PORT') ?: 25),
    'secure'     => getenv('SMTP_SECURE') ?: '',     // '', 'ssl', 'tls'
    'username'   => getenv('SMTP_USER') ?: '',
    'password'   => getenv('SMTP_PASS') ?: '',
    'from'       => getenv('MAIL_FROM') ?: 'no-reply@example.com',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'Store',
    'admin'      => getenv('MAIL_ADMIN') ?: 'admin@example.com',
    'debug'      => false, // true لعرض تفاصيل SMTP أثناء التطوير
];
