<?php

class View {
    public static function render($view, $args = []) {
        extract($args, EXTR_SKIP);
        
        $file = APP_PATH . "/views/$view.php";
        if (file_exists($file)) {
            require $file;
        } else {
            $installerFile = ROOT_PATH . "/installer/views/$view.php";
            if (file_exists($installerFile)) {
                require $installerFile;
            } else {
                echo "<p style='color:red;'>View $view not found.</p>";
            }
        }
    }
}
