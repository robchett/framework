<?php

namespace core\classes;

use \classes\get as _get;

abstract class kml {

    /**
     * @var string
     */
    private $content = '';
    /**
     * @var string
     */
    private $html = '';
    /**
     * @var int
     */
    private $open_folders = 0;
    /**
     * @var array
     */
    private $path = array();
    /**
     * @var string
     */
    private $styles = '';

    /**
     *
     */
    public function __construct() {
    }

    /**
     * @param $colour
     * @param \track\track_point[] $coordinates
     * @param string $altitude_mode
     * @param bool $extrude
     * @return string
     */
    public static function create_linestring($colour, array $coordinates, $altitude_mode = 'absolute', $extrude = false) {
        $xml = '';
        $xml .= '<Placemark>';
        $xml .= '<styleUrl>' . $colour . '</styleUrl>';
        $xml .= '<LineString>';
        $xml .= '<altitudeMode>' . $altitude_mode . '</altitudeMode>';
        if ($extrude)
            $xml .= '<extrude>' . $extrude . '</extrude>';
        $xml .= '<coordinates>';
        foreach ($coordinates as $coord) {
            $xml .= $coord->get_kml_coordinate();
        }
        $xml .= '</coordinates > ';
        $xml .= '</LineString > ';
        $xml .= '</Placemark>';
        return $xml;
    }

    /**
     * @return string
     */
    public static function get_kml_footer() {
        return "\n" . '</Document>';
    }

    /**
     * @return string
     */
    public static function get_kml_header() {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n\t" . '<Document><open>1</open>';
    }

    /**
     * @param $min
     * @param $max
     * @return string
     */
    public static function get_scale($min, $max) {
        $xml = '
    <ScreenOverlay>
        <Icon>
            <href><![CDATA[http://chart.apis.google.com/chart?cht=lc&chs=40x200&chd=e:AAAA,BbBb,DkDk,FsFs,H1H1,J9J9,LYLY,NhNh,PpPp,RyRy,T6T6,VVVV,XeXe,ZmZm,bvbv,d3d3,gAgA,hbhb,jjjj,lsls,n0n0,p9p9,rYrY,tgtg,vpvp,xxxx,z6z6,1V1V,3d3d,5m5m,7u7u,9393,....&chf=bg,s,ffffff00%7cc,s,ffffffcc&chxt=r&chxr=0,' . $min . ',' . $max . '&chxs=0,ffffff&chm=b,0000ff,0,1,1%7cb,0020ff,1,2,1%7cb,003fff,2,3,1%7cb,005fff,3,4,1%7cb,007fff,4,5,1%7cb,009fff,5,6,1%7cb,00bfff,6,7,1%7cb,00dfff,7,8,1%7cb,00fffe,8,9,1%7cb,00ffde,9,10,1%7cb,00ffbf,10,11,1%7cb,00ff9f,11,12,1%7cb,00ff7f,12,13,1%7cb,00ff5f,13,14,1%7cb,00ff3f,14,15,1%7cb,00ff1f,15,16,1%7cb,00ff00,16,17,1%7cb,20ff00,17,18,1%7cb,3fff00,18,19,1%7cb,5fff00,19,20,1%7cb,7fff00,20,21,1%7cb,9fff00,21,22,1%7cb,bfff00,22,23,1%7cb,dfff00,23,24,1%7cb,fffe00,24,25,1%7cb,ffde00,25,26,1%7cb,ffbf00,26,27,1%7cb,ff9f00,27,28,1%7cb,ff7f00,28,29,1%7cb,ff5f00,29,30,1%7cb,ff3f00,30,31,1%7cb,ff1f00,31,32,1&chls=0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0%7c0]]></href>
        </Icon>
        <overlayXY y="1" x="0" xunits="fraction" yunits="fraction"/>
        <screenXY y="1" x="0" xunits="fraction" yunits="fraction"/>
        <size y="0" x="0" xunits="fraction" yunits="fraction"/>
    </ScreenOverlay>';
        return $xml;
    }

    /**
     * @param $s
     * @param $e
     * @return string
     */
    public static function get_timespan($s, $e) {
        return '<TimeSpan>
				<begin>' . date('Y-m-d', $s) . "T" . date('H:i:s', $s) . 'Z</begin>
				<end>' . date('Y-m-d', $e) . "T" . date('H:i:s', $e) . 'Z</end>
			</TimeSpan>';
    }

    /**
     * @param $xml
     */
    public function add($xml) {
        $this->content .= $xml;
    }

    /**
     * @param $html
     */
    public function add_html($html) {
        $this->html .= $html;
    }

    /**
     * @param bool $external
     * @param string $path
     * @return string
     */
    public function compile($external = false, $path = '') {
        $xml = '';
        if (!$external) {
            $xml .= self::get_kml_header();
            $xml .= $this->styles;
        }
        $xml .= $this->content;
        if (!$external)
            $xml .= self::get_kml_footer();

        if (!empty($path)) {
            $path = root . _get::trim_root($path);
            file_put_contents($path, $xml);
            $zip = new \ZipArchive();
            $zip->open(str_replace('.kml', '.kmz', $path), \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
            $zip->addFile($path);
            $zip->close();
            unlink($path);
        }
        return $xml;
    }

    /**
     * @return string
     */
    public function get_html() {
        return $this->html . '</div>';
    }

    /**
     *
     */
    public function get_kml_folder_close() {
        $this->open_folders--;
        $this->content .= '</Folder>';
        $this->html .= '</ul></li>';
        if ($this->open_folders == 0) {
            $this->html .= '</ul>';
        }
        array_pop($this->path);
        if (isset($this->path[$this->open_folders])) {
            $this->path[$this->open_folders]++;
        }
    }

    /**
     * @param $title
     * @param int $visibility
     * @param string $class
     * @param bool $open
     */
    public function get_kml_folder_open($title, $visibility = 1, $class = '', $open = false) {
        $this->content .= '<Folder id="' . _get::fn($title) . '"><name>' . $title . '</name><visibility>' . (int) $visibility . '</visibility>';
        if (!$this->open_folders) {
            $this->html .= '<ul class="kmltree">';
        }
        $this->html .= '<li data-path=\'{"type":"flight","path":[' . $this->get_path($this->open_folders) . ']}\' class="kmltree-item ' . ($class ? $class : 'check') . ' KmlFolder ' . ($visibility ? 'visible' : '') . ' ' . ($open ? 'open' : 'closed') . '"><div class="expander"></div><div class="toggler"></div>' . $title . '<ul>';
        $this->open_folders++;
        $this->path[$this->open_folders] = 0;

        if (!empty($class)) $this->content .= "\n\t<styleUrl>#" . $class . "</styleUrl>";
        if ($open)
            $this->content .= "\n\t<open>1</open>";
    }

    /**
     *
     */
    public function set_animation_styles() {
        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 360; $j += 5) {
                $this->styles .= '<Style id="A' . $i . $j . '"><IconStyle><heading>' . $j . '</heading><Icon><href>http://' . host . '/img/Markers/' . _get::kml_colour($i) . '.gif' . '</href></Icon></IconStyle></Style>';
            }
        }
    }

    /**
     *
     */
    public function set_folder_styles() {
        $this->styles .= '<Style id="hideChildren"><ListStyle><listItemType>checkHideChildren</listItemType></ListStyle></Style>';
        $this->styles .= '<Style id="radioFolder"><ListStyle><listItemType>radioFolder</listItemType></ListStyle></Style>';
    }

    /**
     * @param int $full
     */
    public function set_gradient_styles($full = 0) {
        $this->styles .= '<Style id="shadow"><LineStyle><color>AA000000</color><width>1</width></LineStyle><PolyStyle><color>55AAAAAA</color></PolyStyle></Style>';
        if (!$full)
            $this->styles .= '<Style id="S1"><LineStyle><color>' . _get::colour(0) . '</color><width>2</width></LineStyle></Style>';
        else {
            $grad = new gradient();
            for ($i = 0; $i < 16; $i++) {
                $this->styles .= '<Style id="S' . $i . '"><LineStyle><width>2</width><color>FF' . $grad->get_color_at_value($i / 16) . '</color></LineStyle></Style>';
            }
        }
    }

    /**
     * @return string
     */
    private function get_path() {
        return implode(',', $this->path);
    }
}
