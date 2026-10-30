<?php

class TemplatesController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        ReceiptTemplates::syncDefaults($db);
        $templates = ReceiptTemplates::all(true);
        $selected = ReceiptTemplates::normalizeSelected(App::setting('receipt_template', 'classic'));

        $this->view('../../modules/templates/views/index', [
            'pageTitle' => 'Receipt Templates Studio',
            'activeMenu' => 'templates',
            'templates' => $templates,
            'selectedTemplate' => $selected,
            'previewData' => $this->previewData()
        ]);
    }

    public function select() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $template = ReceiptTemplates::normalizeSelected((string) ($_POST['receipt_template'] ?? 'classic'));
        App::saveSettings(['receipt_template' => $template]);
        $this->flash('success', 'Receipt template selected successfully.');
        $this->redirect('templates/index');
    }

    public function edit($id = null) {
        $db = Database::getInstance();
        ReceiptTemplates::syncDefaults($db);
        $template = null;
        if (is_numeric($id) && (int) $id > 0) {
            $template = $db->fetch("SELECT * FROM receipt_templates WHERE id = ? LIMIT 1", [(int) $id]);
        }
        if (!$template && is_string($id) && trim($id) !== '') {
            $cleanKey = ReceiptTemplates::sanitizeKey($id);
            $template = $db->fetch("SELECT * FROM receipt_templates WHERE template_key = ? LIMIT 1", [$cleanKey]);
        }
        if (!$template) {
            $cleanKey = ReceiptTemplates::sanitizeKey((string) $id);
            $defaults = ReceiptTemplates::defaults();
            if (isset($defaults[$cleanKey])) {
                $template = $defaults[$cleanKey];
            } else {
                $template = [
                    'id' => 0,
                    'template_key' => 'custom-' . time(),
                    'template_name' => 'Custom Template',
                    'template_type' => 'thermal',
                    'paper_width_mm' => 80,
                    'accent_color' => '#0f6dff',
                    'font_family' => 'Segoe UI',
                    'base_template' => 'classic',
                    'html_block' => '',
                    'layout_notes' => '',
                    'status' => 'active',
                    'is_system' => 0
                ];
            }
        }

        $this->view('../../modules/templates/views/edit', [
            'pageTitle' => 'Edit Receipt Template - ' . ($template['template_name'] ?? 'Template'),
            'activeMenu' => 'templates',
            'template' => $template,
            'previewData' => $this->previewData()
        ]);
    }

    public function saveTemplate() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['template_name'] ?? ''));
        $key = strtolower(trim((string) ($_POST['template_key'] ?? '')));
        $type = strtolower(trim((string) ($_POST['template_type'] ?? 'thermal')));
        if (!in_array($type, ['thermal', 'document', 'credit'], true)) {
            $type = 'thermal';
        }
        $defaultWidth = ($type === 'document') ? 148 : 80;
        $width = max(40, min(300, (int) ($_POST['paper_width_mm'] ?? $defaultWidth)));
        $accent = trim((string) ($_POST['accent_color'] ?? '#0f6dff'));
        $font = trim((string) ($_POST['font_family'] ?? 'Segoe UI'));
        $notes = trim((string) ($_POST['layout_notes'] ?? ''));
        $htmlBlock = trim((string) ($_POST['html_block'] ?? ''));
        $baseTemplate = ReceiptTemplates::sanitizeBase((string) ($_POST['base_template'] ?? ''));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));

        if ($name === '' && $htmlBlock !== '') {
            $name = ($type === 'thermal' ? 'Thermal Receipt ' : 'Document Invoice ') . date('M d h:i A');
        }
        if ($name === '') {
            $this->flash('error', 'Template name is required.');
            $this->redirect('templates/index');
        }
        if ($key === '') {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $key = trim($key, '-');
        }
        if ($key === '') {
            $key = ($type === 'thermal' ? 'thermal-' : 'doc-') . time();
        }
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key);
        if ((string) ($_POST['base_template'] ?? '') === '') {
            $baseTemplate = ReceiptTemplates::suggestBaseTemplate($notes . ' ' . $name, $type);
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = '#0f6dff';
        }
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'active';
        }

        if ($id > 0) {
            $exists = $db->fetch("SELECT id FROM receipt_templates WHERE template_key = ? AND id != ? LIMIT 1", [$key, $id]);
            if ($exists) {
                $this->flash('error', 'Template key already exists.');
                $this->redirect('templates/index');
            }
            $db->query("
                UPDATE receipt_templates
                SET template_key = ?, template_name = ?, template_type = ?, paper_width_mm = ?, accent_color = ?, font_family = ?, base_template = ?, html_block = ?, layout_notes = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$key, $name, $type, $width, $accent, $font, $baseTemplate, $htmlBlock, $notes, $status, $id]);
            $this->flash('success', 'Template updated successfully.');
        } else {
            $exists = $db->fetch("SELECT id FROM receipt_templates WHERE template_key = ? LIMIT 1", [$key]);
            if ($exists) {
                $key .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }
            $db->query("
                INSERT INTO receipt_templates (template_key, template_name, template_type, paper_width_mm, accent_color, font_family, base_template, html_block, is_system, layout_notes, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
            ", [$key, $name, $type, $width, $accent, $font, $baseTemplate, $htmlBlock, $notes, $status]);
            $this->flash('success', 'Template created successfully.');
        }

        $this->redirect('templates/index');
    }

    public function deleteTemplate($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $template = $db->fetch("SELECT is_system FROM receipt_templates WHERE id = ? LIMIT 1", [(int) $id]);
        if (!empty($template['is_system'])) {
            $db->query("UPDATE receipt_templates SET status = 'archived', updated_at = NOW() WHERE id = ?", [(int) $id]);
            $this->flash('success', 'System template archived.');
            $this->redirect('templates/index');
        }
        $db->query("DELETE FROM receipt_templates WHERE id = ?", [(int) $id]);
        $this->flash('success', 'Template deleted.');
        $this->redirect('templates/index');
    }

    private function previewData() {
        return [
            'company' => App::setting('company_name', 'DevAltum POS'),
            'address' => App::setting('company_address', 'Lake City'),
            'whatsapp' => App::setting('whatsapp_number', '+923101648600'),
            'footer' => App::setting('receipt_footer', 'Thank you for shopping with us.'),
            'receipt_id' => 'INV-MA-4',
            'date' => date('M d, Y h:i A'),
            'cashier' => $_SESSION['user_name'] ?? 'Admin',
            'customer' => 'Walk-in Customer',
            'items' => [
                ['name' => 'Ottogi Jin Ramen Mild', 'unit' => '1 Gram', 'qty' => 1, 'price' => 2000, 'total' => 2000],
                ['name' => 'Premium Demo Item', 'unit' => '250 Gram', 'qty' => 2, 'price' => 850, 'total' => 1700],
            ],
            'subtotal' => 3700,
            'discount' => 0,
            'total' => 3700,
            'paid' => 3700,
            'remaining' => 0
        ];
    }
}
