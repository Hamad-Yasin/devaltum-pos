<?php

class WhatsAppSender {

    /**
     * Clean and normalize phone number into standard international format without '+' or '00'
     */
    public static function formatPhone(string $phone, string $defaultCountryCode = '92'): string {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) return '';

        // E.g. Pakistan 03001234567 -> 923001234567
        if (str_starts_with($clean, '0')) {
            $clean = $defaultCountryCode . substr($clean, 1);
        }
        return $clean;
    }

    /**
     * Generate direct 1-Click WhatsApp URL (wa.me)
     */
    public static function getChatLink(string $phone, string $message): string {
        $formattedPhone = self::formatPhone($phone);
        return "https://wa.me/{$formattedPhone}?text=" . urlencode($message);
    }

    /**
     * Send message via active WhatsApp Gateway API (UltraMsg / Evolution / Cloud API / GreenAPI)
     */
    public static function sendApiMessage(string $phone, string $message): array {
        $formattedPhone = self::formatPhone($phone);
        if (empty($formattedPhone)) {
            return ['success' => false, 'error' => 'Invalid recipient phone number.'];
        }

        $provider = (string) App::setting('whatsapp_provider', 'wame'); // 'wame', 'ultramsg', 'evolution', 'cloud_api'
        $apiUrl   = (string) App::setting('whatsapp_api_url', '');
        $apiToken = (string) App::setting('whatsapp_api_token', '');
        $instance = (string) App::setting('whatsapp_instance_id', '');

        // If configured for direct link or no API key, return link
        if ($provider === 'wame' || empty($apiUrl)) {
            return [
                'success' => true,
                'mode'    => 'link',
                'link'    => self::getChatLink($formattedPhone, $message)
            ];
        }

        // Dispatch via cURL
        try {
            $ch = curl_init();
            $payload = [];

            if ($provider === 'ultramsg') {
                $url = rtrim($apiUrl, '/') . "/{$instance}/messages/chat";
                $payload = [
                    'token' => $apiToken,
                    'to'    => $formattedPhone,
                    'body'  => $message
                ];
            } else { // Generic / Evolution API format
                $url = rtrim($apiUrl, '/') . '/message/sendText/' . $instance;
                $payload = [
                    'number'  => $formattedPhone,
                    'text'    => $message,
                    'options' => ['delay' => 1200, 'presence' => 'composing']
                ];
            }

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'apikey: ' . $apiToken,
                    'Authorization: Bearer ' . $apiToken
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return ['success' => false, 'error' => 'cURL Error: ' . $err];
            }

            $json = json_decode($response, true);
            return [
                'success'   => ($httpCode >= 200 && $httpCode < 300),
                'http_code' => $httpCode,
                'response'  => $json ?: $response
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build Pre-formatted Sale Bill WhatsApp Message
     */
    public static function buildInvoiceText(array $invoice, array $items, string $storeName): string {
        $grandTotal = number_format((float)($invoice['grand_total'] ?? 0), 2);
        $invoiceNum = $invoice['invoice_number'] ?? 'N/A';
        $date       = date('d M Y, h:i A', strtotime($invoice['created_at'] ?? 'now'));

        $text = "🧾 *{$storeName}*\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "📄 *Invoice:* `{$invoiceNum}`\n";
        $text .= "📅 *Date:* {$date}\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";

        foreach ($items as $item) {
            $qty   = $item['quantity'];
            $price = number_format((float)$item['unit_price'], 2);
            $tot   = number_format((float)$item['total'], 2);
            $name  = $item['product_name'] ?? 'Item';
            $text .= "▫️ {$name}\n";
            $text .= "   {$qty} x Rs. {$price} = *Rs. {$tot}*\n";
        }

        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "💰 *Grand Total:* *Rs. {$grandTotal}*\n";
        if (!empty($invoice['paid_amount'])) {
            $text .= "💵 *Paid:* Rs. " . number_format((float)$invoice['paid_amount'], 2) . "\n";
        }
        if (!empty($invoice['change_amount']) && (float)$invoice['change_amount'] > 0) {
            $text .= "🔄 *Change Return:* Rs. " . number_format((float)$invoice['change_amount'], 2) . "\n";
        }
        if (!empty($invoice['due_amount']) && (float)$invoice['due_amount'] > 0) {
            $text .= "⚠️ *Remaining Due:* Rs. " . number_format((float)$invoice['due_amount'], 2) . "\n";
        }
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "Thank you for shopping with us! 🙏";

        return $text;
    }
}
