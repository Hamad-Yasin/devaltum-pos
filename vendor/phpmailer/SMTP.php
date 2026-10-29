<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer RFC821 / RFC2821 SMTP client
 */
class SMTP {
    const VERSION = '6.9.1';
    const DEFAULT_PORT = 25;
    const DEFAULT_SECURE_PORT = 465;
    const MAX_LINE_LENGTH = 998;
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;

    public $do_debug = self::DEBUG_OFF;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 30;
    public $Timelimit = 30;

    protected $smtp_conn;
    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => ''
    ];
    protected $helo_rply;
    protected $server_caps;
    protected $last_reply = '';

    public function connect($host, $port = null, $timeout = 30, $options = []): bool {
        $this->setError('');
        if ($this->connected()) {
            $this->setError('Already connected to a server');
            return false;
        }
        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }

        $socket_context = stream_context_create($options);
        set_error_handler([$this, 'errorHandler']);
        $this->smtp_conn = stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $socket_context
        );
        restore_error_handler();

        if (!is_resource($this->smtp_conn)) {
            $this->setError('Failed to connect to server: ' . $errstr, (string)$errno);
            return false;
        }

        stream_set_timeout($this->smtp_conn, $timeout, 0);
        $announce = $this->get_lines();
        return true;
    }

    public function startTLS(): bool {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return (bool) $crypto_ok;
    }

    public function authenticate($username, $password, $authtype = null): bool {
        if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', 334)) {
            return false;
        }
        if (!$this->sendCommand(base64_encode($username), 'User', 334)) {
            return false;
        }
        if (!$this->sendCommand(base64_encode($password), 'Password', 235)) {
            return false;
        }
        return true;
    }

    public function connected(): bool {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                $this->close();
                return false;
            }
            return true;
        }
        return false;
    }

    public function close(): void {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    public function hello($host = ''): bool {
        return (bool) ($this->sendHello('EHLO', $host) || $this->sendHello('HELO', $host));
    }

    protected function sendHello($hello, $host): bool {
        $noerror = $this->sendCommand($hello . ' ' . $host, $hello, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parseHelloFields($hello);
        } else {
            $this->server_caps = null;
        }
        return $noerror;
    }

    protected function parseHelloFields($type): void {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $n => $s) {
            $s = trim(substr($s, 4));
            if (empty($s)) continue;
            $fields = explode(' ', $s);
            if (!empty($fields)) {
                $name = strtoupper($fields[0]);
                if ($name === 'AUTH' && !empty($fields[1])) {
                    $this->server_caps['AUTH'] = array_slice($fields, 1);
                } else {
                    $this->server_caps[$name] = true;
                }
            }
        }
    }

    public function mail($from): bool {
        return $this->sendCommand('MAIL FROM:<' . $from . '>', 'MAIL', 250);
    }

    public function recipient($address, $dsn = ''): bool {
        return $this->sendCommand('RCPT TO:<' . $address . '>', 'RCPT', [250, 251]);
    }

    public function data(): bool {
        return $this->sendCommand('DATA', 'DATA', 354);
    }

    public function client_send($data, $checkquote = true): bool {
        if ($checkquote && strpos($data, '.') === 0) {
            $data = '.' . $data;
        }
        return (bool) fwrite($this->smtp_conn, $data);
    }

    public function sendCommand($commandstring, $command, $expect): bool {
        if (!$this->connected()) {
            $this->setError('Called ' . $command . '() without being connected');
            return false;
        }
        $this->client_send($commandstring . "\r\n", false);
        $this->last_reply = $this->get_lines();
        $code = (int) substr($this->last_reply, 0, 3);
        if (is_array($expect)) {
            return in_array($code, $expect, true);
        }
        return $code === $expect;
    }

    protected function get_lines(): string {
        $data = '';
        $endtime = 0;
        if (!is_resource($this->smtp_conn)) {
            return $data;
        }
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, 515);
            $data .= $str;
            if (isset($str[3]) && $str[3] === ' ') {
                break;
            }
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                break;
            }
            if ($endtime && time() > $endtime) {
                break;
            }
        }
        return $data;
    }

    public function getError(): array {
        return $this->error;
    }

    public function getLastReply(): string {
        return $this->last_reply;
    }

    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = ''): void {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex,
        ];
    }

    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0): void {
        $this->setError('Error: ' . $errmsg, (string)$errno);
    }
}
