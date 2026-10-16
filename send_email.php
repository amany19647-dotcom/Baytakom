<?php
/**
 * send_email.php - دوال إرسال الإشعارات الإلكترونية
 * الدوال الأساسية موجودة في config.php
 * هذا الملف يوفر دوال إضافية خاصة بالحجوزات
 */

require_once 'config.php';

function sendBookingConfirmation($booking_id, $user_email, $user_name, $service_name, $booking_date, $booking_time, $address) {
    $subject = 'تأكيد حجز الخدمة - بيتكم';
    $message = '
    <div style="direction:rtl; font-family:Tajawal, sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تأكيد حجز الخدمة</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $user_name . '</strong>،</p>
            <p>تم استلام طلب حجز الخدمة بنجاح:</p>
            <ul style="list-style:none; padding:0;">
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>الخدمة:</strong> ' . $service_name . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>التاريخ:</strong> ' . date('d/m/Y', strtotime($booking_date)) . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>الوقت:</strong> ' . date('h:i A', strtotime($booking_time)) . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>العنوان:</strong> ' . $address . '</li>
            </ul>
            <p style="margin-top:20px;">سيتم التواصل معك قريباً لتأكيد الموعد.</p>
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($user_email, $subject, $message);
}

function sendBookingStatusUpdate($booking_id, $user_email, $user_name, $service_name, $status) {
    $status_labels = array(
        'pending' => 'معلق',
        'assigned' => 'تم التخصيص',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي'
    );
    $status_text = isset($status_labels[$status]) ? $status_labels[$status] : $status;

    $subject = 'تحديث حالة الحجز - بيتكم';
    $message = '
    <div style="direction:rtl; font-family:Tajawal, sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تحديث حالة الحجز</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $user_name . '</strong>،</p>
            <p>تم تحديث حالة حجزك للخدمة <strong>' . $service_name . '</strong> إلى:</p>
            <div style="text-align:center; padding:15px; margin:15px 0; border-radius:10px; background:rgba(18,168,143,0.1);">
                <span style="font-size:24px; font-weight:800; color:#12A88F;">' . $status_text . '</span>
            </div>
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($user_email, $subject, $message);
}

function sendNewBookingNotification($provider_email, $provider_name, $client_name, $service_name, $booking_date, $booking_time, $address) {
    $subject = 'طلب حجز جديد - بيتكم';
    $message = '
    <div style="direction:rtl; font-family:Tajawal, sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">طلب حجز جديد</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $provider_name . '</strong>،</p>
            <p>لديك طلب حجز جديد من العميل <strong>' . $client_name . '</strong>:</p>
            <ul style="list-style:none; padding:0;">
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>الخدمة:</strong> ' . $service_name . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>التاريخ:</strong> ' . date('d/m/Y', strtotime($booking_date)) . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>الوقت:</strong> ' . date('h:i A', strtotime($booking_time)) . '</li>
                <li style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);"><strong>العنوان:</strong> ' . $address . '</li>
            </ul>
            <p style="margin-top:20px;">يرجى مراجعة الطلب وتأكيد الموعد.</p>
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($provider_email, $subject, $message);
}
?>