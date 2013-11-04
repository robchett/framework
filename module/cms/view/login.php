<?php
namespace core\module\cms\view;

use classes\get;
use module\cms\form\cms_builder_form;
use module\cms\form\cms_login_form;

abstract class login extends cms_view {

    public function get_view() {
        try {
            get::ini('server', 'mysql');
        } catch (\Exception $e) {
            $form = new cms_builder_form();
            return $form->get_html();
        }
        $form = new cms_login_form();
        return $form->get_html();
    }

    public function get() {
        return $this->get_view()->get();
    }
}
