<?php
declare(strict_types=1);

/** أرسل رؤوس أمان قياسية */
function send_security_headers(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');

    // Content Security Policy مبسّط (عدّله حسب الحاجة)
    $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:";
    header("Content-Security-Policy: $csp");
}

// استدعِها في أعلى header.php
