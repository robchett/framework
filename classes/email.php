<?php

namespace core\classes;

class email {

    public $subject;
    public $content;
    public $recipients = [
        '' => [],
        'cc' => [],
        'bcc' => []
    ];

    public $replacements = [];

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
        return mail(implode(',', $this->recipients['']), $this->do_replace($this->subject), $this->do_replace($this->content));
    }

}
