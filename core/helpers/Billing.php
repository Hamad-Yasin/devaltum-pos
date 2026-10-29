<?php

class Billing {
    public static function calculateExpiryDate($startDate, $durationDays) {
        $start = strtotime((string) $startDate);
        if ($start === false) {
            $start = time();
        }
        $days = max(1, (int) $durationDays);
        return date('Y-m-d H:i:s', strtotime("+{$days} days", $start));
    }

    public static function runDailyAutomation() {
        $db = Database::getInstance();
        self::markExpiredStores($db);
        self::sendExpiryReminders($db, 7);
        self::sendExpiryReminders($db, 3);
        self::sendExpiryReminders($db, 0);
        self::sendInstallmentReminders($db, 2);
    }

    public static function markExpiredStores($db) {
        $db->query("
            UPDATE stores
            SET status = 'expired', updated_at = NOW()
            WHERE subscription_expires_at IS NOT NULL
              AND subscription_expires_at < NOW()
              AND status IN ('active', 'suspended')
              AND (parent_store_id IS NULL OR parent_store_id = 0)
        ");
    }

    public static function sendExpiryReminders($db, $daysBefore) {
        $daysBefore = (int) $daysBefore;
        $stores = $db->fetchAll("
            SELECT s.id, s.owner_name, s.store_name, s.phone, s.subscription_expires_at, p.plan_name
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            WHERE s.status = 'active'
              AND s.subscription_expires_at IS NOT NULL
              AND DATE(s.subscription_expires_at) = DATE(DATE_ADD(NOW(), INTERVAL ? DAY))
              AND (s.parent_store_id IS NULL OR s.parent_store_id = 0)
        ", [$daysBefore]);

        foreach ($stores as $store) {
            $alreadySent = $db->fetch("
                SELECT id
                FROM subscription_reminders
                WHERE store_id = ? AND reminder_type = ? AND reminder_for_date = DATE(?)
                LIMIT 1
            ", [(int) $store['id'], "expiry_{$daysBefore}", $store['subscription_expires_at']]);
            if ($alreadySent) {
                continue;
            }

            $message = "Hello {$store['owner_name']}\n\nYour POS subscription will expire on " .
                date('Y-m-d', strtotime($store['subscription_expires_at'])) .
                ".\nPlease renew your plan to avoid service interruption.";

            $targetPhone = self::resolveStoreWhatsappPhone((int) ($store['id'] ?? 0), (string) ($store['phone'] ?? ''));
            $sent = self::sendWhatsapp($targetPhone, $message);
            $db->query("
                INSERT INTO subscription_reminders (store_id, reminder_type, reminder_for_date, channel, is_sent, created_at)
                VALUES (?, ?, DATE(?), 'whatsapp', ?, NOW())
            ", [(int) $store['id'], "expiry_{$daysBefore}", $store['subscription_expires_at'], $sent ? 1 : 0]);
        }
    }

    public static function sendPlanActivationMessage($storeId, $planName, $expiryDate) {
        $db = Database::getInstance();
        $store = $db->fetch("SELECT owner_name, phone FROM stores WHERE id = ? LIMIT 1", [(int) $storeId]);
        if (!$store) {
            return false;
        }
        $targetPhone = self::resolveStoreWhatsappPhone((int) $storeId, (string) ($store['phone'] ?? ''));
        $msg = "Hello {$store['owner_name']}\n\nYour subscription plan {$planName} has been activated.\nExpiry Date: " .
            date('Y-m-d', strtotime((string) $expiryDate)) .
            "\n\nThank you for using our POS system.";
        return self::sendWhatsapp($targetPhone, $msg);
    }

    public static function sendPaymentConfirmationMessage($storeId, $amount, $expiryDate) {
        $db = Database::getInstance();
        $store = $db->fetch("SELECT owner_name, phone FROM stores WHERE id = ? LIMIT 1", [(int) $storeId]);
        if (!$store) {
            return false;
        }
        $targetPhone = self::resolveStoreWhatsappPhone((int) $storeId, (string) ($store['phone'] ?? ''));
        $msg = "Hello {$store['owner_name']}\n\nPayment received: " . App::money($amount) .
            "\nNext Expiry: " . date('Y-m-d', strtotime((string) $expiryDate));
        return self::sendWhatsapp($targetPhone, $msg);
    }

    public static function sendWhatsapp($phone, $message) {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return false;
        }

        $provider = strtolower((string) App::setting('whatsapp_provider', 'none'));
        if ($provider === 'meta') {
            return self::sendViaMetaCloud($phone, $message);
        }
        return false;
    }

    private static function sendViaMetaCloud($phone, $message) {
        $token = trim((string) App::setting('meta_access_token', ''));
        $phoneId = trim((string) App::setting('meta_phone_number_id', ''));
        if ($token === '' || $phoneId === '') {
            return false;
        }

        $url = "https://graph.facebook.com/v21.0/{$phoneId}/messages";
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message]
        ]);
        return self::curlPost($url, (string) $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
    }

    private static function curlPost($url, $payload, $headers) {
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private static function resolveStoreWhatsappPhone($storeId, $fallbackPhone = '') {
        $storeId = (int) $storeId;
        $fallbackPhone = trim((string) $fallbackPhone);
        if ($storeId <= 0) {
            return $fallbackPhone;
        }

        $db = Database::getInstance();
        $setting = $db->fetch("
            SELECT setting_value
            FROM store_settings
            WHERE store_id = ? AND setting_key = 'whatsapp_number'
            LIMIT 1
        ", [$storeId]);

        $configured = trim((string) ($setting['setting_value'] ?? ''));
        return $configured !== '' ? $configured : $fallbackPhone;
    }

    public static function sendInstallmentReminders($db, $daysBefore) {
        $daysBefore = (int) $daysBefore;
        $stores = $db->fetchAll("
            SELECT s.id, s.owner_name, s.store_name, s.phone, s.email, s.next_monthly_charge_date, s.monthly_charge_amount
            FROM stores s
            WHERE s.status = 'active'
              AND s.next_monthly_charge_date IS NOT NULL
              AND DATE(s.next_monthly_charge_date) = DATE(DATE_ADD(CURDATE(), INTERVAL ? DAY))
              AND (s.parent_store_id IS NULL OR s.parent_store_id = 0)
        ", [$daysBefore]);

        foreach ($stores as $store) {
            $alreadySent = $db->fetch("
                SELECT id
                FROM subscription_reminders
                WHERE store_id = ? AND reminder_type = ? AND reminder_for_date = DATE(?)
                LIMIT 1
            ", [(int) $store['id'], "installment_{$daysBefore}", $store['next_monthly_charge_date']]);
            if ($alreadySent) {
                continue;
            }

            $amount = (float) ($store['monthly_charge_amount'] ?? 0);
            $dueDate = date('M d, Y', strtotime($store['next_monthly_charge_date']));
            
            // 1. Send professional HTML email
            $subject = "Payment Reminder: Upcoming Installment for {$store['store_name']}";
            $emailBody = self::buildReminderEmailTemplate($store['owner_name'], $store['store_name'], $amount, $dueDate);
            $emailSent = self::sendEmail($store['email'], $subject, $emailBody);

            // 2. Send WhatsApp reminder message
            $message = "Assalam o Alaikum {$store['owner_name']},\n\nThis is a friendly reminder that your monthly POS installment of " . App::money($amount) . " is due on {$dueDate} for your store: {$store['store_name']}.\n\nPlease clear your payment to keep your POS active.\n\nThank you,\nDevAltum POS";
            $targetPhone = self::resolveStoreWhatsappPhone((int) $store['id'], (string) ($store['phone'] ?? ''));
            $whatsappSent = self::sendWhatsapp($targetPhone, $message);

            // Record reminder state
            $db->query("
                INSERT INTO subscription_reminders (store_id, reminder_type, reminder_for_date, channel, is_sent, created_at)
                VALUES (?, ?, DATE(?), 'hybrid', ?, NOW())
            ", [(int) $store['id'], "installment_{$daysBefore}", $store['next_monthly_charge_date'], ($emailSent || $whatsappSent) ? 1 : 0]);
        }
    }

    public static function sendEmail($to, $subject, $bodyHtml) {
        $to = trim((string) $to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: DevAltum POS <no-reply@aljwc.shop>',
            'Reply-To: support@aljwc.shop',
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
    }

    public static function buildReminderEmailTemplate($ownerName, $storeName, $amount, $dueDate) {
        $amountFormatted = App::money($amount);
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Upcoming Installment Reminder</title>
            <style>
                body { font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
                .email-header { background: linear-gradient(135deg, #1e3a8a, #0f6dff); padding: 40px 20px; text-align: center; color: #ffffff; }
                .email-header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
                .email-body { padding: 30px; line-height: 1.6; }
                .email-body p { margin-top: 0; font-size: 15px; color: #4b5563; }
                .kpi-container { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 24px 0; display: flex; justify-content: space-between; }
                .kpi-block { flex: 1; text-align: center; }
                .kpi-block small { display: block; color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
                .kpi-block strong { font-size: 18px; color: #0f172a; }
                .btn-pay { display: block; width: fit-content; margin: 30px auto 0; padding: 12px 30px; background: #0f6dff; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 10px; box-shadow: 0 4px 12px rgba(15,109,255,0.25); text-align: center; }
                .email-footer { background-color: #f9fafb; padding: 20px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>Payment Reminder</h1>
                </div>
                <div class="email-body">
                    <p>Dear ' . htmlspecialchars($ownerName) . ',</p>
                    <p>This is a friendly reminder that the monthly payment installment for your store <strong>' . htmlspecialchars($storeName) . '</strong> is due soon.</p>
                    
                    <div class="kpi-container">
                        <div class="kpi-block" style="border-right: 1px solid #e2e8f0;">
                            <small>Installment Amount</small>
                            <strong style="color: #10b981;">' . $amountFormatted . '</strong>
                        </div>
                        <div class="kpi-block">
                            <small>Due Date</small>
                            <strong style="color: #ef4444;">' . htmlspecialchars($dueDate) . '</strong>
                        </div>
                    </div>

                    <p>Please log in to your dashboard and complete the payment using your preferred method (EasyPaisa, JazzCash, or Bank Transfer) to avoid any interruption in service.</p>
                    <a href="https://aljwc.shop/public/plans/index" class="btn-pay">Pay Installment Now</a>
                </div>
                <div class="email-footer">
                    &copy; ' . date('Y') . ' DevAltum POS. All rights reserved.<br>
                    Need help? Contact support on WhatsApp +923101648600.
                </div>
            </div>
        </body>
        </html>';
    }

    public static function buildReceiptEmailTemplate($ownerName, $storeName, $amount, $date, $nextDue = null) {
        $amountFormatted = App::money($amount);
        $nextDueRow = '';
        if ($nextDue) {
            $nextDueRow = '
            <div style="margin-top: 20px; padding: 14px; border-radius: 10px; background-color: #fef08a; border: 1px solid #fef08a; color: #854d0e; text-align: center; font-size: 13px; font-weight: 700;">
                Next Installment Due Date: ' . date('M d, Y', strtotime($nextDue)) . '
            </div>';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Payment Receipt</title>
            <style>
                body { font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
                .email-header { background: linear-gradient(135deg, #064e3b, #10b981); padding: 40px 20px; text-align: center; color: #ffffff; }
                .email-header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
                .email-body { padding: 30px; line-height: 1.6; }
                .email-body p { margin-top: 0; font-size: 15px; color: #4b5563; }
                .receipt-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 24px 0; }
                .receipt-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                .receipt-row:last-child { border-bottom: none; font-weight: 800; font-size: 16px; padding-top: 12px; }
                .email-footer { background-color: #f9fafb; padding: 20px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>Payment Received</h1>
                </div>
                <div class="email-body">
                    <p>Dear ' . htmlspecialchars($ownerName) . ',</p>
                    <p>Thank you for your payment. We have successfully processed your installment for your store: <strong>' . htmlspecialchars($storeName) . '</strong>.</p>
                    
                    <div class="receipt-card">
                        <div class="receipt-row">
                            <span style="color: #64748b;">Store Name</span>
                            <strong>' . htmlspecialchars($storeName) . '</strong>
                        </div>
                        <div class="receipt-row">
                            <span style="color: #64748b;">Date Paid</span>
                            <strong>' . htmlspecialchars($date) . '</strong>
                        </div>
                        <div class="receipt-row" style="border-bottom: 2px solid #e2e8f0;">
                            <span style="color: #64748b;">Payment Status</span>
                            <strong style="color: #10b981; text-transform: uppercase;">Paid</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Amount Paid</span>
                            <span style="color: #10b981;">' . $amountFormatted . '</span>
                        </div>
                    </div>

                    ' . $nextDueRow . '

                    <p style="margin-top: 25px;">Your subscription has been kept active. You can continue accessing all modules without any issues.</p>
                </div>
                <div class="email-footer">
                    &copy; ' . date('Y') . ' DevAltum POS. All rights reserved.<br>
                    Need help? Contact support on WhatsApp +923101648600.
                </div>
            </div>
        </body>
        </html>';
    }
}
