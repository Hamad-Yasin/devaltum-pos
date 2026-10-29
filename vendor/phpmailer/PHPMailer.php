<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer - Full-featured email creation and transfer class
 */
class PHPMailer {
    const CHARSET_UTF8 = 'utf-8';
    const ENCODING_BASE64 = 'base64';

    public $Priority = null;
    public $CharSet = self::CHARSET_UTF8;
    public $ContentType = 'text/html';
    public $Encoding = self::ENCODING_BASE64;
    public $From = 'root@localhost';
    public $FromName = 'DevAltum POS';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $WordWrap = 0;
    public $Mailer = 'smtp';
    public $Host = 'localhost';
    public $Port = 25;
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $Timeout = 30;
    public $SMTPDebug = 0;
    public $isHTML = true;

    protected $smtp = null;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary = [];
    protected $errorInfo = '';

    public function __construct($exceptions = null) {
        $this->boundary[1] = 'b1_' . md5(uniqid(time()));
        $this->boundary[2] = 'b2_' . md5(uniqid(time()));
    }

    public function isSMTP(): void {
        $this->Mailer = 'smtp';
    }

    public function isHTML($isHtml = true): void {
        $this->isHTML = (bool) $isHtml;
        $this->ContentType = $isHtml ? 'text/html' : 'text/plain';
    }

    public function addAddress($address, $name = ''): bool {
        return $this->addAnAddress('to', $address, $name);
    }

    public function addCC($address, $name = ''): bool {
        return $this->addAnAddress('cc', $address, $name);
    }

    public function addBCC($address, $name = ''): bool {
        return $this->addAnAddress('bcc', $address, $name);
    }

    public function addReplyTo($address, $name = ''): bool {
        return $this->addAnAddress('ReplyTo', $address, $name);
    }

    protected function addAnAddress($kind, $address, $name = ''): bool {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->errorInfo = 'Invalid email address: ' . $address;
            return false;
        }
        $this->{$kind}[] = [$address, $name];
        if ($kind !== 'ReplyTo') {
            $this->all_recipients[strtolower($address)] = true;
        }
        return true;
    }

    public function setFrom($address, $name = '', $auto = true): bool {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->errorInfo = 'Invalid from address: ' . $address;
            return false;
        }
        $this->From = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) {
            $this->Sender = $address;
        }
        return true;
    }

    public function addAttachment($path, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment'): bool {
        if (!@is_file($path) || !is_readable($path)) {
            $this->errorInfo = 'Could not access file: ' . $path;
            return false;
        }
        if (empty($name)) {
            $name = basename($path);
        }
        if (empty($type)) {
            $type = mime_content_type($path) ?: 'application/octet-stream';
        }
        $this->attachment[] = [
            0 => $path,
            1 => $name,
            2 => $name,
            3 => $encoding,
            4 => $type,
            5 => false,
            6 => $disposition,
            7 => $name,
        ];
        return true;
    }

    public function send(): bool {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $e) {
            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    public function preSend(): bool {
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            $this->errorInfo = 'You must provide at least one recipient email address.';
            return false;
        }
        return true;
    }

    public function postSend(): bool {
        if ($this->Mailer === 'smtp') {
            return $this->smtpSend();
        }
        return $this->mailSend();
    }

    protected function smtpSend(): bool {
        $this->smtp = new SMTP();
        $this->smtp->Timeout = $this->Timeout;

        $hosts = explode(';', $this->Host);
        $connected = false;
        foreach ($hosts as $host) {
            $port = $this->Port;
            $host = trim($host);
            if (str_starts_with($host, 'ssl://') || $this->SMTPSecure === 'ssl') {
                $port = $port ?: 465;
            }
            if ($this->smtp->connect($host, $port, $this->Timeout)) {
                $connected = true;
                break;
            }
        }

        if (!$connected) {
            $this->errorInfo = 'SMTP connect() failed.';
            return false;
        }

        $this->smtp->hello(gethostname() ?: 'localhost');

        if ($this->SMTPSecure === 'tls') {
            if (!$this->smtp->startTLS()) {
                $this->errorInfo = 'StartTLS failed.';
                return false;
            }
            $this->smtp->hello(gethostname() ?: 'localhost');
        }

        if ($this->SMTPAuth) {
            if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                $this->errorInfo = 'SMTP authentication failed.';
                return false;
            }
        }

        if (!$this->smtp->mail($this->From)) {
            $this->errorInfo = 'MAIL FROM command failed.';
            return false;
        }

        foreach ($this->all_recipients as $email => $bool) {
            if (!$this->smtp->recipient($email)) {
                $this->errorInfo = 'RCPT TO failed for: ' . $email;
                return false;
            }
        }

        if (!$this->smtp->data()) {
            $this->errorInfo = 'DATA command failed.';
            return false;
        }

        $header = $this->createHeader();
        $body = $this->createBody();
        $this->smtp->client_send($header . "\r\n" . $body . "\r\n.\r\n");
        $this->smtp->close();
        return true;
    }

    protected function mailSend(): bool {
        $toArr = [];
        foreach ($this->to as $t) {
            $toArr[] = $t[1] ? "{$t[1]} <{$t[0]}>" : $t[0];
        }
        $to = implode(', ', $toArr);
        $header = $this->createHeader();
        return @mail($to, $this->Subject, $this->createBody(), $header);
    }

    public function createHeader(): string {
        $headers = [];
        $headers[] = 'Date: ' . date('D, j M Y H:i:s O');
        $headers[] = 'From: ' . ($this->FromName ? "=?UTF-8?B?" . base64_encode($this->FromName) . "?= <{$this->From}>" : $this->From);
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($this->Subject) . '?=';
        $headers[] = 'Message-ID: <' . md5(uniqid(time())) . '@' . (gethostname() ?: 'localhost') . '>';
        $headers[] = 'X-Mailer: PHPMailer ' . SMTP::VERSION . ' (https://github.com/PHPMailer/PHPMailer)';
        $headers[] = 'MIME-Version: 1.0';

        if (empty($this->attachment)) {
            $headers[] = 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet;
            $headers[] = 'Content-Transfer-Encoding: ' . $this->Encoding;
        } else {
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $this->boundary[1] . '"';
        }
        return implode("\r\n", $headers);
    }

    public function createBody(): string {
        if (empty($this->attachment)) {
            return $this->Encoding === self::ENCODING_BASE64 ? chunk_split(base64_encode($this->Body)) : $this->Body;
        }

        $body = "--{$this->boundary[1]}\r\n";
        $body .= "Content-Type: {$this->ContentType}; charset={$this->CharSet}\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($this->Body)) . "\r\n";

        foreach ($this->attachment as $att) {
            $content = file_get_contents($att[0]);
            $body .= "--{$this->boundary[1]}\r\n";
            $body .= "Content-Type: {$att[4]}; name=\"{$att[1]}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: {$att[6]}; filename=\"{$att[1]}\"\r\n\r\n";
            $body .= chunk_split(base64_encode($content)) . "\r\n";
        }
        $body .= "--{$this->boundary[1]}--";
        return $body;
    }

    public function getErrorInfo(): string {
        return $this->errorInfo;
    }
}
