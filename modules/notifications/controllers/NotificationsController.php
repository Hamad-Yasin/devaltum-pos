<?php

class NotificationsController extends Controller {

    public function __construct() {
        AuthMiddleware::handle();
    }

    public function index() {
        $tab = strtolower(trim($_GET['tab'] ?? 'new'));
        if (!in_array($tab, ['new', 'seen'], true)) {
            $tab = 'new';
        }

        $this->view('../../modules/notifications/views/index', [
            'pageTitle' => Auth::isSuperAdmin() ? 'Platform Notifications' : 'Notifications',
            'activeMenu' => Auth::isSuperAdmin() ? 'sa_notifications' : 'notifications',
            'tab' => $tab,
            'isSuperadminNotifications' => Auth::isSuperAdmin(),
            'newNotifications' => App::notificationsByState('new', 300),
            'seenNotifications' => App::notificationsByState('seen', 300)
        ]);
    }

    public function open($id) {
        $notification = App::notificationById((int) $id);
        if (!$notification) {
            $this->redirect('notifications/index');
        }

        App::markNotificationSeen((int) $id);
        if (Auth::isSuperAdmin()) {
            $storeId = (int) ($notification['related_store_id'] ?? $notification['store_id'] ?? 0);
            $this->redirect($storeId > 0 ? 'superadmin/storeProfile/' . $storeId : 'notifications/index');
        }
        $this->redirect('credits/action/' . (int) $notification['invoice_id']);
    }

    public function markSeen($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        App::markNotificationSeen((int) $id);
        $this->redirect('notifications/index?tab=new');
    }

    public function markAllSeen() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        App::markAllNotificationsSeen();
        $this->redirect('notifications/index?tab=seen');
    }

    public function feed() {
        $this->jsonResponse([
            'count' => App::unreadCreditNotificationsCount(),
            'notifications' => App::latestNotifications(5)
        ]);
    }
}
