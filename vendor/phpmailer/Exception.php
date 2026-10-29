<?php
namespace PHPMailer\PHPMailer;

use Exception as BaseException;

/**
 * PHPMailer exception handler
 */
class Exception extends BaseException {
    /**
     * Prettify the error message.
     *
     * @return string
     */
    public function errorMessage(): string {
        return '<strong>' . htmlspecialchars($this->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</strong><br />\n";
    }
}
