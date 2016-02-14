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