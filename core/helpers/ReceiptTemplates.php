<?php

class ReceiptTemplates {
    public static function defaults() {
        return [
            'classic' => [
                'template_key' => 'classic',
                'template_name' => 'Classic Receipt',
                'name' => 'Classic Receipt',
                'tagline' => 'Current professional receipt with clean sections, QR verification and thermal print support.',
                'badge' => 'Current Style',
                'template_type' => 'thermal',
                'group' => 'thermal',
                'paper_width_mm' => 80,
                'accent_color' => '#0f6dff',
                'font_family' => 'Segoe UI',
                'base_template' => 'classic',
                'layout_notes' => 'Clean thermal receipt with centered business header, compact totals and QR footer.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'compact' => [
                'template_key' => 'compact',
                'template_name' => 'Compact Thermal',
                'name' => 'Compact Thermal',
                'tagline' => 'Tighter spacing for small thermal rolls and quick counter printing.',
                'badge' => 'Fast Print',
                'template_type' => 'thermal',
                'group' => 'thermal',
                'paper_width_mm' => 58,
                'accent_color' => '#111827',
                'font_family' => 'Courier New',
                'base_template' => 'compact',
                'layout_notes' => 'Narrow thermal receipt with monospace type and compact rows.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'premium' => [
                'template_key' => 'premium',
                'template_name' => 'Premium Soft',
                'name' => 'Premium Soft',
                'tagline' => 'Softer rounded layout with stronger header presence for branded receipts.',
                'badge' => 'Premium',
                'template_type' => 'thermal',
                'group' => 'thermal',
                'paper_width_mm' => 80,
                'accent_color' => '#1d4ed8',
                'font_family' => 'Segoe UI',
                'base_template' => 'premium',
                'layout_notes' => 'Soft branded receipt with bold gradient header and modern spacing.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'invoice' => [
                'template_key' => 'invoice',
                'template_name' => 'Invoice Pro',
                'name' => 'Invoice Pro',
                'tagline' => 'Full invoice layout with business header, bill-to block, item table and signature footer.',
                'badge' => 'Invoice',
                'template_type' => 'document',
                'group' => 'document',
                'paper_width_mm' => 148,
                'accent_color' => '#0b4f66',
                'font_family' => 'Segoe UI',
                'base_template' => 'invoice',
                'layout_notes' => 'Document invoice inspired by the admin hardcoded design with border rails and invoice box.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'executive' => [
                'template_key' => 'executive',
                'template_name' => 'Executive Ledger',
                'name' => 'Executive Ledger',
                'tagline' => 'Corporate invoice layout with navy header, boxed summary and sharp table lines.',
                'badge' => 'Executive',
                'template_type' => 'document',
                'group' => 'document',
                'paper_width_mm' => 148,
                'accent_color' => '#102a43',
                'font_family' => 'Segoe UI',
                'base_template' => 'executive',
                'layout_notes' => 'Formal document invoice with dark executive header and ledger-like table rows.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'emerald' => [
                'template_key' => 'emerald',
                'template_name' => 'Emerald Statement',
                'name' => 'Emerald Statement',
                'tagline' => 'Fresh green professional invoice with soft panels and easy-read totals.',
                'badge' => 'Statement',
                'template_type' => 'document',
                'group' => 'document',
                'paper_width_mm' => 148,
                'accent_color' => '#047857',
                'font_family' => 'Segoe UI',
                'base_template' => 'emerald',
                'layout_notes' => 'Premium green statement with soft cards, rounded panels and readable totals.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'monogram' => [
                'template_key' => 'monogram',
                'template_name' => 'Monogram Bill',
                'name' => 'Monogram Bill',
                'tagline' => 'Minimal premium invoice with left accent rail, clean typography and formal signatures.',
                'badge' => 'Minimal',
                'template_type' => 'document',
                'group' => 'document',
                'paper_width_mm' => 148,
                'accent_color' => '#111827',
                'font_family' => 'Segoe UI',
                'base_template' => 'monogram',
                'layout_notes' => 'Minimal document bill with left rail, clean spacing and strong type hierarchy.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
            'thermal_mono' => [
                'template_key' => 'thermal_mono',
                'template_name' => 'Monospace Sales Thermal',
                'name' => 'Monospace Sales Thermal',
                'tagline' => 'Classic thermal invoice layout with Courier font, dashed dividers, and monospace totals.',
                'badge' => 'Classic Thermal',
                'template_type' => 'thermal',
                'group' => 'thermal',
                'paper_width_mm' => 80,
                'accent_color' => '#111827',
                'font_family' => 'Courier New',
                'base_template' => 'thermal_mono',
                'layout_notes' => 'Classic monospace thermal print receipt with dashed borders, ITEM QTY RATE TOTAL headers, and clean cash summary.',
                'html_block' => self::defaultMonoHtml(),
                'status' => 'active',
                'is_system' => true,
            ],
            'retail_standard' => [
                'template_key' => 'retail_standard',
                'template_name' => 'Master Retail Standard',
                'name' => 'Master Retail Standard',
                'tagline' => 'Exact retail superstore thermal format with bordered items grid, Total Items & Qty summary, boxed Net Payable, Rupees in words, and Code128 vector barcode.',
                'badge' => 'Retail Slip',
                'template_type' => 'thermal',
                'group' => 'thermal',
                'paper_width_mm' => 80,
                'accent_color' => '#111827',
                'font_family' => 'Arial',
                'base_template' => 'retail_standard',
                'layout_notes' => 'Clean boxed thermal receipt matching retail superstore formats with grid table, total items & qty counters, net payable highlight, rupee words and vector barcode.',
                'html_block' => '',
                'status' => 'active',
                'is_system' => true,
            ],
        ];
    }

    public static function defaultMonoHtml() {
        return <<<'HTML'
<div class="mono-receipt" style="font-family:'Courier New',Courier,monospace; font-size:12px; color:#000; width:100%; max-width:320px; margin:0 auto; padding:10px 0;">
  <div style="text-align:center; margin-bottom:12px;">
    <h2 style="font-size:17px; font-weight:800; margin:0 0 4px; text-transform:uppercase; letter-spacing:0.5px;">{{company_name}}</h2>
    <div style="font-size:11px; margin-bottom:2px;">{{company_address}}</div>
    <div style="font-size:11px; font-weight:700;">NTN: 8294102-7</div>
    <div style="font-size:12px; font-weight:800; margin-top:6px;">*** SALES INVOICE ***</div>
  </div>
  <div style="border-top:1px dashed #000; margin:8px 0;"></div>
  <div style="font-size:11.5px; line-height:1.4;">
    <div style="display:flex; justify-content:space-between;"><span>Invoice #: <strong>{{invoice_no}}</strong></span><span>{{date}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Cashier: {{cashier}}</span><span>{{time}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Customer: <strong>{{customer}}</strong></span><span>{{phone}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Payment: <strong>{{payment}}</strong></span></div>
  </div>
  <div style="border-top:1px dashed #000; margin:8px 0;"></div>
  <table style="width:100%; border-collapse:collapse; font-size:11.5px; text-align:left;">
    <thead>
      <tr style="font-weight:800; border-bottom:1px dashed #000;">
        <th style="padding:4px 0; text-align:left;">ITEM</th>
        <th style="padding:4px 0; text-align:center; width:45px;">QTY</th>
        <th style="padding:4px 0; text-align:right; width:55px;">RATE</th>
        <th style="padding:4px 0; text-align:right; width:65px;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      {{items}}
    </tbody>
  </table>
  <div style="border-top:1px dashed #000; margin:8px 0;"></div>
  <div style="font-size:12px; line-height:1.5;">
    <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><strong>{{subtotal}}</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Discount:</span><strong>{{discount}}</strong></div>
    <div style="border-top:1px dashed #000; margin:6px 0;"></div>
    <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:800; margin:4px 0;"><span>GRAND TOTAL:</span><span>{{total}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Cash Paid:</span><span>{{paid}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Change / Due:</span><span>{{remaining}}</span></div>
  <div style="border-top:1px dashed #000; margin:10px 0 8px;"></div>
  <div style="text-align:center; font-size:11px; font-weight:800; text-transform:uppercase;">
    <div>THANK YOU FOR YOUR VISIT!</div>
  </div>
  <div style="border-top:1px dashed #000; margin:8px 0 4px; text-align:center;">
    {{developer_credit}}
  </div>
</div>
HTML;
    }

    public static function all($includeDrafts = false) {
        $templates = [];
        try {
            $rows = Database::getInstance()->fetchAll("
                SELECT *
                FROM receipt_templates
                WHERE " . ($includeDrafts ? "status != 'archived'" : "status = 'active'") . "
                ORDER BY is_system DESC, id ASC
            ");
            foreach ($rows as $row) {
                $normalized = self::normalizeRow($row);
                $templates[$normalized['template_key']] = $normalized;
            }
        } catch (Throwable $e) {
            // Keep receipts available even if the optional template table is unavailable.
        }
        $defaults = self::defaults();
        foreach ($defaults as $k => $def) {
            if (!isset($templates[$k])) {
                $templates[$k] = $def;
            }
        }
        return $templates;
    }

    public static function syncDefaults($db) {
        foreach (self::defaults() as $template) {
            $exists = $db->fetch("SELECT id FROM receipt_templates WHERE template_key = ? LIMIT 1", [$template['template_key']]);
            if ($exists) {
                $db->query("
                    UPDATE receipt_templates
                    SET is_system = 1, updated_at = COALESCE(updated_at, NOW())
                    WHERE id = ?
                ", [(int) $exists['id']]);
                continue;
            }

            $db->query("
                INSERT INTO receipt_templates
                    (template_key, template_name, template_type, paper_width_mm, accent_color, font_family, base_template, html_block, is_system, layout_notes, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'active', NOW())
            ", [
                $template['template_key'],
                $template['template_name'],
                $template['template_type'],
                (int) $template['paper_width_mm'],
                $template['accent_color'],
                $template['font_family'],
                $template['base_template'],
                $template['html_block'] ?? '',
                $template['layout_notes']
            ]);
        }
    }

    public static function resolve($key) {
        $key = self::sanitizeKey($key);
        $templates = self::all(false);
        if (!isset($templates[$key])) {
            return $templates['classic'];
        }
        $template = $templates[$key];
        $base = self::sanitizeBase($template['base_template'] ?? $template['template_key']);
        if (!isset(self::defaults()[$base])) {
            $base = ($template['group'] ?? 'thermal') === 'document' ? 'invoice' : 'classic';
        }
        $template['base_template'] = $base;
        return $template;
    }

    public static function normalizeSelected($key) {
        return self::resolve($key)['template_key'];
    }

    public static function baseKey($key) {
        return self::resolve($key)['base_template'];
    }

    public static function isDocument($key) {
        return (self::resolve($key)['group'] ?? 'thermal') === 'document';
    }

    public static function suggestBaseTemplate($instructions, $type = 'document') {
        $text = strtolower((string) $instructions);
        if (strpos($text, 'thermal') !== false || strpos($text, 'roll') !== false || $type === 'thermal') {
            if (strpos($text, 'compact') !== false || strpos($text, 'small') !== false || strpos($text, '58') !== false) return 'compact';
            if (strpos($text, 'premium') !== false || strpos($text, 'modern') !== false || strpos($text, 'gradient') !== false) return 'premium';
            return 'classic';
        }
        if (strpos($text, 'green') !== false || strpos($text, 'emerald') !== false || strpos($text, 'fresh') !== false) return 'emerald';
        if (strpos($text, 'minimal') !== false || strpos($text, 'mono') !== false || strpos($text, 'left rail') !== false) return 'monogram';
        if (strpos($text, 'dark') !== false || strpos($text, 'navy') !== false || strpos($text, 'corporate') !== false || strpos($text, 'executive') !== false) return 'executive';
        return 'invoice';
    }

    public static function cssVariables($template) {
        $accent = self::sanitizeColor($template['accent_color'] ?? '#0f6dff');
        $font = trim((string) ($template['font_family'] ?? 'Segoe UI'));
        if ($font === '') $font = 'Segoe UI';
        return "--receipt-accent: {$accent}; --receipt-font: '" . str_replace("'", '', $font) . "', 'Segoe UI', Arial, sans-serif;";
    }

    public static function renderHtmlBlock($html, $data) {
        $html = (string) $html;
        if (trim($html) === '') return '';
        $itemsHtml = '';
        foreach (($data['items'] ?? []) as $item) {
            $unitSub = !empty($item['product_unit_label']) ? '<small style="display:block; font-size:10px; color:#555; font-weight:normal;">' . Security::escape((string) $item['product_unit_label']) . '</small>' : '';
            $itemsHtml .= '<tr style="border-bottom:1px dashed #eee;">'
                . '<td style="padding:5px 0; vertical-align:top;"><strong>' . Security::escape((string) ($item['product_name'] ?? $item['name'] ?? 'Product')) . '</strong>' . $unitSub . '</td>'
                . '<td style="padding:5px 0; text-align:center; vertical-align:top;">' . Security::escape(App::formatQuantity((float) ($item['quantity'] ?? $item['qty'] ?? 0))) . '</td>'
                . '<td style="padding:5px 0; text-align:right; vertical-align:top;">' . Security::escape(App::money((float) ($item['price'] ?? 0))) . '</td>'
                . '<td style="padding:5px 0; text-align:right; vertical-align:top; font-weight:800;">' . Security::escape(App::money((float) ($item['total'] ?? 0))) . '</td>'
                . '</tr>';
        }
        $replacements = [
            '{{company_name}}' => Security::escape((string) ($data['company_name'] ?? 'DevAltum POS')),
            '{{company_address}}' => Security::escape((string) ($data['company_address'] ?? '')),
            '{{whatsapp}}' => Security::escape((string) ($data['whatsapp'] ?? '')),
            '{{invoice_no}}' => Security::escape((string) ($data['invoice_no'] ?? 'INV-MA-1')),
            '{{date}}' => Security::escape((string) ($data['date'] ?? date('M d, Y h:i A'))),
            '{{time}}' => Security::escape((string) ($data['time'] ?? date('h:i A'))),
            '{{cashier}}' => Security::escape((string) ($data['cashier'] ?? 'Admin')),
            '{{customer}}' => Security::escape((string) ($data['customer'] ?? 'Walk-in Customer')),
            '{{phone}}' => Security::escape((string) ($data['phone'] ?? '-')),
            '{{status}}' => Security::escape((string) ($data['status'] ?? 'PAID')),
            '{{payment}}' => Security::escape((string) ($data['payment'] ?? 'Cash')),
            '{{channel}}' => Security::escape((string) ($data['channel'] ?? 'Retail')),
            '{{items}}' => $itemsHtml,
            '{{subtotal}}' => Security::escape(App::money((float) ($data['subtotal'] ?? 0))),
            '{{discount}}' => Security::escape(App::money((float) ($data['discount'] ?? 0))),
            '{{tax_label}}' => Security::escape((string) ($data['tax_label'] ?? 'Tax')),
            '{{tax_rate}}' => Security::escape((string) ($data['tax_rate'] ?? '0')),
            '{{tax_amount}}' => Security::escape(App::money((float) ($data['tax_amount'] ?? 0))),
            '{{fbr_pos_id}}' => Security::escape((string) ($data['fbr_pos_id'] ?? '')),
            '{{total}}' => Security::escape(App::money((float) ($data['total'] ?? 0))),
            '{{total_items}}' => (string) count($data['items'] ?? []),
            '{{paid}}' => Security::escape(App::money((float) ($data['paid'] ?? 0))),
            '{{remaining}}' => Security::escape(App::money((float) ($data['remaining'] ?? 0))),
            '{{footer}}' => Security::escape((string) ($data['footer'] ?? 'Thank you for shopping with us.')),
            '{{qr}}' => (string) ($data['qr'] ?? ''),
            '{{developer_credit}}' => App::devAltumReceiptFooterHtml(),
            '{{dev_credit}}' => App::devAltumReceiptFooterHtml(),
            '{{devaltum_credit}}' => App::devAltumReceiptFooterHtml(),
        ];
        $rendered = strtr($html, $replacements);

        // Guarantee DevAltum signature is ALWAYS shown on any template, including all newly created templates
        if (stripos($rendered, 'devaltum-receipt-branding') === false && stripos($rendered, 'Software By DevAltum') === false) {
            $creditWrapper = '<div style="margin-top:10px; padding-top:6px; border-top:1px dashed #cbd5e1; text-align:center;">' . App::devAltumReceiptFooterHtml() . '</div>';
            if (preg_match('/(<\/div>\s*)$/i', $rendered, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                $rendered = substr($rendered, 0, $pos) . $creditWrapper . substr($rendered, $pos);
            } else {
                $rendered .= $creditWrapper;
            }
        }
        return $rendered;
    }

    public static function sanitizeKey($key) {
        $key = strtolower(trim((string) $key));
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key);
        return trim($key, '-') ?: 'classic';
    }

    public static function sanitizeBase($base) {
        $base = self::sanitizeKey($base);
        return isset(self::defaults()[$base]) ? $base : 'classic';
    }

    public static function sanitizeColor($color) {
        $color = trim((string) $color);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#0f6dff';
    }

    private static function normalizeRow($row) {
        $type = strtolower((string) ($row['template_type'] ?? 'thermal'));
        if (!in_array($type, ['thermal', 'document', 'credit'], true)) {
            $type = 'thermal';
        }
        $base = self::sanitizeBase($row['base_template'] ?? '');
        if ($type === 'thermal' && in_array($base, ['invoice', 'executive', 'emerald', 'monogram'], true)) {
            $base = 'classic';
        } elseif ($type === 'document' && !in_array($base, ['invoice', 'executive', 'emerald', 'monogram'], true)) {
            $base = 'invoice';
        }
        $group = ($type === 'document') ? 'document' : 'thermal';
        return [
            'id' => (int) ($row['id'] ?? 0),
            'template_key' => self::sanitizeKey($row['template_key'] ?? ''),
            'template_name' => (string) ($row['template_name'] ?? 'Custom Template'),
            'name' => (string) ($row['template_name'] ?? 'Custom Template'),
            'tagline' => trim((string) ($row['layout_notes'] ?? 'Custom Super Admin receipt template.')) ?: 'Custom Super Admin receipt template.',
            'badge' => 'Custom',
            'template_type' => $type,
            'group' => $group,
            'paper_width_mm' => max(40, min(300, (int) ($row['paper_width_mm'] ?? ($group === 'document' ? 148 : 80)))),
            'accent_color' => self::sanitizeColor($row['accent_color'] ?? '#0f6dff'),
            'font_family' => (string) ($row['font_family'] ?? 'Segoe UI'),
            'base_template' => $base,
            'html_block' => (string) ($row['html_block'] ?? ''),
            'layout_notes' => (string) ($row['layout_notes'] ?? ''),
            'status' => (string) ($row['status'] ?? 'draft'),
            'is_system' => !empty($row['is_system']),
        ];
    }
}
