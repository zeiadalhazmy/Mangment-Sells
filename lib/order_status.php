<?php
// lib/order_status.php

function allowedTransitions(): array {
  return [
    'placed'    => ['paid','cancelled'],
    'paid'      => ['shipped','cancelled','refunded'],
    'shipped'   => ['delivered','refunded'],
    'delivered' => ['refunded'],
    'cancelled' => [],       // لا انتقال بعد الإلغاء
    'refunded'  => [],       // لا انتقال بعد الاسترجاع
  ];
}

function canTransition(string $from, string $to): bool {
  $map = allowedTransitions();
  return isset($map[$from]) && in_array($to, $map[$from], true);
}

/**
 * تحديث الحالة مع حفظ الطابع الزمني والسجل.
 * $actor: اسم المستخدم في لوحة الإدارة (خذها من session مثلاً).
 */
function updateOrderStatus(PDO $pdo, int $orderId, string $to, string $note = '', string $actor = 'admin'): bool {
  // اجلب الحالة الحالية
  $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
  $stmt->execute([$orderId]);
  $from = $stmt->fetchColumn();
  if ($from === false) return false;

  if (!canTransition($from, $to)) {
    throw new RuntimeException("غير مسموح بالانتقال من $from إلى $to");
  }

  // حدّث order بحسب الحالة الجديدة
  $now = date('Y-m-d H:i:s');
  $cols = "status = :to, status_updated_at = :now, status_note = :note";
  if ($to === 'paid')      $cols .= ", paid_at = :now";
  if ($to === 'shipped')   $cols .= ", shipped_at = :now";
  if ($to === 'delivered') $cols .= ", delivered_at = :now";
  if ($to === 'cancelled') $cols .= ", cancelled_at = :now";
  if ($to === 'refunded')  $cols .= ", refunded_at = :now";

  $sql = "UPDATE orders SET $cols WHERE id = :id";
  $up  = $pdo->prepare($sql);
  $ok  = $up->execute([':to'=>$to, ':now'=>$now, ':note'=>$note, ':id'=>$orderId]);
  if (!$ok) return false;

  // أضف سجل
  $log = $pdo->prepare("INSERT INTO order_status_logs (order_id, from_status, to_status, note, changed_by) VALUES (?,?,?,?,?)");
  $log->execute([$orderId, $from, $to, $note, $actor]);

  return true;
}
