<?php

namespace core\classes;

use classes\ini as _ini;

class email{

    public $subject;
    public $content;
    public $recipients = [
        '' => [],
        'cc' => [],
        'bcc' => []
    ];

    public $replacements = [];

    const METHOD_SENDMAIL = 1;
    const METHOD_SES = 2;

    public static $sending_method = self::METHOD_SENDMAIL;

    public static function set_statics() {
        static::$sending_method = _ini::get('email', 'method', self::METHOD_SENDMAIL);
    }

    public function set_recipients($base, $cc = [], $bcc = []) {
        $this->recipients[''] = $base;
        $this->recipients['cc'] = $cc;
        $this->recipients['bcc'] = $bcc;
    }

    public function set_subject($subject) {
        $this->subject = $subject;
    }

    public function set_content($content) {
        $this->content = $content;
    }

    public function load_template($file) {
        if (!file_exists($file)) {
            throw new \Exception('Email template not found');
        } else {
            $this->content = file_get_contents($file);
        }
    }

    protected function do_replace($string) {
        return str_replace(array_keys($this->replacements), array_values($this->replacements), $string);
    }

    public function send() {
        $content = $this->do_replace($this->content);
        if (static::$sending_method == static::METHOD_SENDMAIL) {
            return mail(implode(',', $this->recipients['']), $this->do_replace($this->subject), $content);
        } else if (static::$sending_method == static::METHOD_SES) {
            require_once root . '/library/aws.phar';
            $controller = new \Aws\Ses\SesClient([
                'version' => 'latest',
                'profile' => _ini::get('aws', 'profile'),
                'region' => _ini::get('aws', 'region', 'eu-west-1'),
            ]);
            return $controller->sendEmail([
                'Source' => _ini::get('email', 'from_address'),
                'Destination' => [
                    'ToAddresses' => $this->recipients[''],
                    'CcAddresses' => $this->recipients['cc'],
                    'BccAddresses' => $this->recipients['bcc'],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $this->subject,
                        'Charset' => 'UTF8',
                    ],
                    'Body' => [
                        'Text' => [
                            'Data' => '',
                            'Charset' => 'UTF8',
                        ],
                        'Html' => [
                            'Data' => $content,
                            'Charset' => 'UTF8',
                        ],
                    ],
                ]
            ]);
        }
    }
}
