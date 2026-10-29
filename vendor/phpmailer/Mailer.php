<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    /**
     * Send general HTML email using configured SMTP settings or defaults
     */
    public static function send(string $to, string $subject, string $htmlBody, array $attachments = [], array $options = []): array {
        $mail = new PHPMailer(true);

        try {
            $smtpHost = (string) App::setting('smtp_host', 'smtp.gmail.com');
            $smtpPort = (int) App::setting('smtp_port', 587);
            $smtpUser = (string) App::setting('smtp_username', '');
            $smtpPass = (string) App::setting('smtp_password', '');
            $smtpSec  = (string) App::setting('smtp_encryption', 'tls');
            $fromMail = (string) App::setting('smtp_from_email', $smtpUser ?: 'noreply@devaltum.com');
            $fromName = (string) App::setting('company_name', 'DevAltum POS');

            if (!empty($smtpUser) && !empty($smtpPass)) {
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = $smtpSec;
                $mail->Port       = $smtpPort;
            } else {
                $mail->Mailer = 'mail'; // fallback to PHP mail()
            }

            $mail->setFrom($fromMail, $fromName);
            $mail->addAddress($to, $options['to_name'] ?? '');
            
            if (!empty($options['reply_to'])) {
                $mail->addReplyTo($options['reply_to'], $options['reply_name'] ?? '');
            }

            foreach ($attachments as $att) {
                if (is_string($att) && file_exists($att)) {
                    $mail->addAttachment($att);
                } elseif (is_array($att) && !empty($att['path']) && file_exists($att['path'])) {
                    $mail->addAttachment($att['path'], $att['name'] ?? '');
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($subject, $htmlBody);
            $mail->AltBody = strip_tags($htmlBody);

            $sent = $mail->send();
            return [
                'success' => (bool)$sent,
                'message' => $sent ? 'Email sent successfully' : $mail->getErrorInfo()
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Mailer Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send Sale Invoice Email to Customer
     */
    public static function sendInvoiceEmail(string $customerEmail, string $customerName, array $invoice, array $items = []): array {
        $storeName = App::setting('company_name', 'DevAltum POS');
        $subject   = "Invoice #{$invoice['invoice_number']} — {$storeName}";

        $rowsHtml = '';
        foreach ($items as $item) {
            $total = number_format((float)$item['total'], 2);
            $rowsHtml .= "
            <tr>
                <td style='padding:8px; border-bottom:1px solid #e2e8f0;'>{$item['product_name']}</td>
                <td style='padding:8px; border-bottom:1px solid #e2e8f0; text-align:center;'>{$item['quantity']}</td>
                <td style='padding:8px; border-bottom:1px solid #e2e8f0; text-align:right;'>Rs. {$item['unit_price']}</td>
                <td style='padding:8px; border-bottom:1px solid #e2e8f0; text-align:right;'>Rs. {$total}</td>
            </tr>";
        }

        $body = "
        <p>Dear <strong>{$customerName}</strong>,</p>
        <p>Thank you for your business. Here is the summary of your purchase:</p>
        <table style='width:100%; border-collapse:collapse; margin:16px 0;'>
            <thead>
                <tr style='background:#f1f5f9; text-align:left;'>
                    <th style='padding:8px;'>Item</th>
                    <th style='padding:8px; text-align:center;'>Qty</th>
                    <th style='padding:8px; text-align:right;'>Price</th>
                    <th style='padding:8px; text-align:right;'>Total</th>
                </tr>
            </thead>
            <tbody>{$rowsHtml}</tbody>
        </table>
        <div style='text-align:right; font-size:16px; font-weight:bold; color:#0f172a;'>
            Grand Total: Rs. " . number_format((float)$invoice['grand_total'], 2) . "
        </div>";

        return self::send($customerEmail, $subject, $body, [], ['to_name' => $customerName]);
    }

    /**
     * Wrap body in standard modern responsive HTML email shell
     */
    private static function wrapTemplate(string $title, string $content): string {
        $brand = htmlspecialchars((string)App::setting('company_name', 'DevAltum POS'));
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; }
                .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                .header { background: #0f172a; color: #ffffff; padding: 24px; text-align: center; }
                .body { padding: 24px; line-height: 1.6; }
                .footer { background: #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='header'>
                    <h2 style='margin:0; font-size:20px;'>{$brand}</h2>
                </div>
                <div class='body'>
                    {$content}
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " {$brand}. Powered by DevAltum POS.
                </div>
            </div>
        </body>
        </html>";
    }
}
