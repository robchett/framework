<?php

/**
 * Class coordinate
 */
class coordinate {

    /**
     * @param bool $as_rad *
     *
     * @return float
     */
    public function lat($as_rad = false) { }

    /**
     * @param bool $as_rad *
     *
     * @return float
     */
    public function lng($as_rad = false) { }

    /** @return int */
    public function ele() { }

    /** @return int */
    public function timestamp() { }

    /** @param float $lat */
    public function set_lat($lat) { }

    /** @param float $lng */
    public function set_lng($lng) { }

    /** @param float $ele */
    public function set_ele($ele) { }

    /**
     * @param coordinate $point
     *
     * @return float
     * */
    public function get_distance_to(coordinate $point) { }

    /**
     * @param coordinate $point
     *
     * @return float
     * */
    public function get_bearing_to(coordinate $point) { }
}

/**
 * Class coordinate_set
 */
class coordinate_set {

    /**
     */
    public function __construct() { }

    public function parse_igc($string) { }

    public function trim() { }

    public function repair() { }

    public function set_graph_values() { }

    public function set_ranges() { }

    public function set_section($index) { }

    /**
     * @param int $i
     *
     * @return int
     */
    public function part_length($i) { }

    /**
     * @param int $i
     *
     * @return int
     */
    public function part_duration($i) { }

    /** @return int */
    public function count() { }

    /** @return int */
    public function part_count() { }

    /** @return int */
    public function date() { }

    /** @return bool */
    public function has_height_data() { }

    /**
     * @param coordinate $coordinate
     */
    public function set(\coordinate $coordinate) { }

    /**
     * @param $index
     *
     * @return \lat_lng
     */
    public function get($index) { }

    /** @return \lat_lng */
    public function first() { }

    /** @return \lat_lng */
    public function last() { }
}

class task {
    /** @return coordinate */
    public function get($index) { }

    /** @return float */
    public function get_distance($precise = false) { }

    /** @return string */
    public function get_gridref() { }

    /** @return int */
    public function get_duration() { }

    //    /** @return coordinate[] */
    //    public function get_coordinates() { }
}

/**
 * Class distance_map
 */
class distance_map {

    /**
     * @param coordinate_set $set
     */
    public function __construct(coordinate_set $set) { }

    /**
     * @param int $index1
     * @param int $index2
     *
     * @return float
     */
    public function get($index1, $index2) { }

    /** @return task */
    public function score_out_and_return() { }

    /** @return task */
    public function score_triangle() { }

    /** @return task */
    public function score_open_distance_3tp() { }
}

class formatter_kml {

    public function __construct(coordinate_set $set, $name, task $od = null, task $or = null, task $tr = null) { }

    /** @return string KML */
    public function output() { }
}

class formatter_kml_split {

    public function __construct(coordinate_set $set) { }

    /** @return string KML */
    public function output() { }
}


class formatter_js {

    public function __construct(coordinate_set $set, $id, task $od = null, task $or = null, task $tr = null) { }

    /** @return string KML */
    public function output() { }
}