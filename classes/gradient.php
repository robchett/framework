<?php

namespace core\classes;

class gradient {

    /** @var table_array */
    public $color;

    public function __construct() {
        $this->create_default_heat_map_gradient();
    }

    public function create_default_heat_map_gradient() {
        $this->color = new \classes\table_array();
        $this->color[] = new ColorPoint(1, 0, 0, 0.0); // blue
        $this->color[] = new ColorPoint(1, 1, 0, 0.25); // cyan
        $this->color[] = new ColorPoint(0, 1, 0, 0.5); // green
        $this->color[] = new ColorPoint(0, 1, 1, 0.75); // yellow
        $this->color[] = new ColorPoint(0, 0, 1, 1.0); // red
    }

    public function get_color_at_value($value) {
        if (count($this->color) == 0) {
            return '';
        }

        for ($i = 0; $i < count($this->color); $i++) {
            $currC = $this->color[$i];
            if ($value < $currC->val) {
                $prevC = $this->color[max(0, $i - 1)];
                $valueDiff = ($prevC->val - $currC->val);
                $fractBetween = ($valueDiff == 0) ? 0 : ($value - $currC->val) / $valueDiff;
                $red = ($prevC->r - $currC->r) * $fractBetween + $currC->r;
                $green = ($prevC->g - $currC->g) * $fractBetween + $currC->g;
                $blue = ($prevC->b - $currC->b) * $fractBetween + $currC->b;
                return $this->to_hex($red, $green, $blue);
            }
        }
        $red = $this->color->last()->r;
        $green = $this->color->last()->g;
        $blue = $this->color->last()->b;
        return $this->to_hex($red, $green, $blue);
    }

    public function to_hex($r, $g, $b) {
        return sprintf('%02s%02s%02s', dechex(255 * $r), dechex(255 * $g), dechex(255 * $b));
    }

}

class  ColorPoint {

    public $r, $g, $b, $val;

    public function __construct($red, $green, $blue, $value) {
        $this->r = $red;
        $this->g = $green;
        $this->b = $blue;
        $this->val = $value;
    }
}
