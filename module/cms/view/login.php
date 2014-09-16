<?php
namespace core\module\cms\view;

use classes\ini;
use module\cms\form\cms_builder_form;
use module\cms\form\cms_login_form;

abstract class login extends cms_view {

    public function get_view() {
        try {
            ini::get('mysql', 'server');
        } catch (\Exception $e) {
            $form = new cms_builder_form();
            return $form->get_html();
        }
        $form = new cms_login_form();
        $form->wrapper_class[] = 'container';
        $form->wrapper_class[] = 'form-signin';
        return $form->get_html();
    }

    public function get() {
        return $this->get_view()->get();
    }

    protected function get_nav() {}
}
