<?php
namespace core\module\cms\form;

use classes\ajax;
use core\classes\db;
use form\field_boolean;
use form\field_string;
use form\form;

abstract class new_module_form extends form {

    public $namespace;
    public $nestable;

    /** @var string */
    public $table_name;
    /** @var string */
    public $title;
    /** @var string */
    public $primary_key;
    /** @var int */
    public $gid;
    public $title_label;

    public function __construct() {
        parent::__construct(
            [
                form::create('field_link', 'gid', array('link_module' => '\module\cms\object\_cms_group', 'link_field' => 'title', 'label' => 'Group')),
                new field_string('primary_key', array('label' => 'Primary Key')),
                new field_string('title', array('label' => 'Title')),
                new field_string('title_label', array('label' => 'Title Label')),
                new field_string('table_name', array('label' => 'Table Name')),
                form::create('field_string', 'namespace')->set_attr('label', 'Namespace <small>can be blank</small>')->set_attr('required', false),
                new field_boolean('nestable', array('label' => 'Nestable')),
            ]
        );
    }

    public function do_submit() {
        if (parent::do_submit()) {
            db::query('CREATE TABLE IF NOT EXISTS `' . $this->table_name . '` (
            `' . $this->primary_key . '` int(4) NOT NULL AUTO_INCREMENT,
            ' . ($this->nestable ? '`parent_' . $this->primary_key . '` int(4) NOT NULL DEFAULT "0",' : '') . '
            `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `cms_created` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00",
            `live` tinyint(1) NOT NULL DEFAULT "0",
            `deleted` tinyint(1) NOT NULL DEFAULT "0",
            `position` int(6) NOT NULL DEFAULT "0",
            `' . \classes\get::fn($this->title_label) . '` varchar(255) NOT NULL,
            PRIMARY KEY (`' . $this->primary_key . '`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;'
            );
            $mid = db::insert('_cms_module')
                ->add_value('gid', $this->gid)
                ->add_value('primary_key', $this->primary_key)
                ->add_value('title', $this->title)
                ->add_value('table_name', $this->table_name)
                ->add_value('namespace', $this->namespace)
                ->execute();
            $id_field = db::insert('_cms_field')
                ->add_value('field_name', $this->primary_key)
                ->add_value('title', 'ID')
                ->add_value('type', 'int')
                ->add_value('mid', $mid)
                ->execute();
            if ($this->nestable) {
                db::insert('_cms_field')
                    ->add_value('field_name', 'parent_' . $this->primary_key)
                    ->add_value('title', 'Parent ID')
                    ->add_value('type', 'link')
                    ->add_value('mid', $mid)
                    ->add_value('link_module', $mid)
                    ->add_value('link_field', (int) $id_field + 2)
                    ->execute();
            }
            db::insert('_cms_field')
                ->add_value('field_name', \classes\get::fn($this->title_label))
                ->add_value('title', $this->title_label)
                ->add_value('type', 'string')
                ->add_value('mid', $mid)
                ->execute();
        }
        $file = null;
        if (!$this->namespace) {
            if (!is_dir(root . '/inc/object/')) {
                mkdir(root . '/inc/object/');
            }
            if (!file_exists(root . '/inc/object/' . $this->table_name)) {
                $file = root . '/inc/object/' . $this->table_name;
            }
        } else {
            if (!is_dir(root . '/inc/module/')) {
                mkdir(root . '/inc/module/');
            }
            if (!is_dir(root . '/inc/module/' . $this->namespace)) {
                mkdir(root . '/inc/module/' . $this->namespace);
            }
            if (!is_dir(root . '/inc/module/' . $this->namespace . '/object/')) {
                mkdir(root . '/inc/module/' . $this->namespace . '/object');
            }
            if (!file_exists(root . '/inc/module/' . $this->namespace . '/object/' . $this->table_name)) {
                $file = root . '/inc/module/' . $this->namespace . '/object/' . $this->table_name;
            }
        }
        if ($file) {
            file_put_contents($file, '
<?php
namespace ' . ($this->namespace ? 'module\\' . $this->namespace . '\\' : '') . 'object;

use classes\table;
use traits\table_trait;

class ' . $this->table_name . ' extends table {

    use table_trait;

    public static $module_id = ' . $mid . ';
    public $table_key = ' . $this->primary_key . ';
    public $' . $this->title . ';

}'
            );
        }
        ajax::add_script('window.location = window.location');
    }
}
