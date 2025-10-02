<?php
// lib/Mailer.php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public function send(string $to, string $subject, string $html, ?string $alt = null): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->cfg['host'];
            $mail->Port       = $this->cfg['port'];
            if (!empty($this->cfg['secure'])) {
                $mail->SMTPSecure = $this->cfg['secure']; // 'tls' or 'ssl'
            }
            $mail->SMTPAuth   = !empty($this->cfg['username']);
            if ($mail->SMTPAuth) {
                $mail->Username = $this->cfg['username'];
                $mail->Password = $this->cfg['password'];
            }
            $mail->SMTPDebug  = $this->cfg['debug'] ? 2 : 0;
            $mail->CharSet    = 'UTF-8';

            // From / To
            $mail->setFrom($this->cfg['from'], $this->cfg['from_name']);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $alt ?: strip_tags($html);

            return $mail->send();
        } catch (Exception $e) {
            // يمكنك تسجيل الخطأ في لوج
            return false;
        }
    }

    /** تحميل قالب وإرجاع HTML */
    public static function render(string $tpl, array $vars = []): string
    {
        $full = __DIR__ . '/../templates/' . ltrim($tpl, '/');
        if (!is_file($full)) {
            return '';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $full;
        return ob_get_clean() ?: '';
    }

    /** إرسال بريد للعميل + الإدارة بعد إنشاء الطلب */
    public function sendOrderEmails(array $order, array $items, array $customer = []): void
    {
        $subject = "تفاصيل طلبك رقم {$order['code']}";

        // ====== رسالة العميل ======
        $htmlCustomer = self::render('email/order_customer.php', [
            'order'    => $order,
            'items'    => $items,
            'customer' => $customer,
            'store'    => $this->cfg['from_name'],
        ]);
        if (!empty($customer['email'])) {
            $this->send($customer['email'], $subject, $htmlCustomer);
        }

        // ====== رسالة الإدارة ======
        $htmlAdmin = self::render('email/order_admin.php', [
            'order'    => $order,
            'items'    => $items,
            'customer' => $customer,
            'store'    => $this->cfg['from_name'],
        ]);
        if (!empty($this->cfg['admin'])) {
            $this->send($this->cfg['admin'], "طلب جديد: {$order['code']}", $htmlAdmin);
        }
    }
}
