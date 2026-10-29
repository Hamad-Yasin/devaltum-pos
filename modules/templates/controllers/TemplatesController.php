<?php

class TemplatesController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $templates = ReceiptTemplates::all(false);
        $selected = ReceiptTemplates::normalizeSelected(App::setting('receipt_template', 'classic'));
        $this->view('../../modules/templates/views/index', [
            'pageTitle' => 'Templates',
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
