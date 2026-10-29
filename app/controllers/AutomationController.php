<?php

class AutomationController extends Controller {

    public function billingDaily() {
        $token = trim((string) ($_GET['token'] ?? ''));
        $expected = trim((string) App::setting('cron_secret_token', ''));
        if ($expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        Billing::runDailyAutomation();
        echo 'OK';
        exit;
    }
}

