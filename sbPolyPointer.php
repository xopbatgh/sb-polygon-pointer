<?php
/**
 * User: Viktor (based on randywendy ideas)
 * Date: 20.01.18
 */

class sbPolygonEngine {

    public $map_width = 500;
    public $map_height = 500;

    public $polygon_coordinates = [] ;

    //list of the polygon lines, that together create a polygon ([x1, y1, x2, y2])
    public $polygon_bounds = [];

    public $point_to_check = [] ;

    // lines we use to create perpendicular from $bounds_box to $point_to_check
    public $perpendicularityLines = [];

    public $bounds_box = [
        'x1' => 0,
        'y1' => 0,
        'x2' => 0,
        'y2' => 0,
    ];

    /*
     * Arg: polygon_coordinates
     * Example: [
                    [55.761515, 37.600375],
                    [55.759428, 37.651156],
                    [55.737112, 37.649566],
                    [55.737649, 37.597301],
                ]
     */
    public function __construct($polygon_coordinates){

        $this->loadPolygon($polygon_coordinates);

    }

    private function preparePolygonVars(){

        $this->polygon_bounds = [];

        $this->perpendicularityLines = [
            'linetop' => [], // x1, y1, x2, y2 (from, from, to, to, crosses_ticks)
            'linebottom' => [],
            'lineleft' => [],
            'lineright' => [],
        ];

        $this->bounds_box = [
            'x1' => 0,
            'y1' => 0,
            'x2' => 0,
            'y2' => 0,
        ];

    }

    public function loadPolygon($polygon_coordinates){

        //$this->point_to_check = [55.757856, 37.600000];

        $this->polygon_coordinates = $polygon_coordinates ;

        $this->preparePolygonVars();


        foreach ($this->polygon_coordinates as $_key => $_list)
            $this->polygon_coordinates[$_key]['coords'] = $this->convertLatLngIntoCoords($_list[0], $_list[1], $this->map_width, $this->map_height);


        foreach ($this->polygon_coordinates as $_key => $_list){

            $nextKey = 0 ;
            if (isset($this->polygon_coordinates[$_key + 1]))
                $nextKey = $_key + 1;


            $_list_next = $this->polygon_coordinates[$nextKey] ;

            $this->polygon_bounds[] = ['x1' => $_list['coords']['x'], 'y1' => $_list['coords']['y'], 'x2' => $_list_next['coords']['x'], 'y2' => $_list_next['coords']['y'], ];

        }



    }

    private function previewBounds($params = []){
        Global $_CFG ;

        $cachedFile = $_CFG['root'] . 'static/img/jj.png';

        $final = imagecreate($this->map_width, $this->map_height);

        $white = imagecolorallocate($final,255,255,255); // color of text

        $redColor = imagecolorallocate($final,255,0,0); // color of text
        $blueColor = imagecolorallocate($final,0,0,255); // color of text
        $greenColor = imagecolorallocate($final,0,255,0); // color of text

        //print '<pre>' . print_r($this->polygon_coordinates, true) . '</pre>';

        $zoomOffset = [
            'minX' => 0,
            'minY' => 0,
        ];

        foreach ($this->polygon_coordinates as $_point){

            if ($zoomOffset['minX'] == 0 OR $_point['coords']['x'] < $zoomOffset['minX'])
                $zoomOffset['minX'] = $_point['coords']['x'];

            if ($zoomOffset['minY'] == 0 OR $_point['coords']['y'] < $zoomOffset['minY'])
                $zoomOffset['minY'] = $_point['coords']['y'];

        }

        $this->zoomed_bounds = $this->polygon_bounds ;

        $zoomCooficient = 1000 * 8;

        foreach ($this->zoomed_bounds as $_key => $_point){

            // обнуляем систему координат
            $this->zoomed_bounds[$_key]['x1'] -= $zoomOffset['minX'];
            $this->zoomed_bounds[$_key]['y1'] -= $zoomOffset['minY'];
            $this->zoomed_bounds[$_key]['x2'] -= $zoomOffset['minX'];
            $this->zoomed_bounds[$_key]['y2'] -= $zoomOffset['minY'];

            $this->zoomed_bounds[$_key]['x1'] *= $zoomCooficient;
            $this->zoomed_bounds[$_key]['y1'] *= $zoomCooficient;
            $this->zoomed_bounds[$_key]['x2'] *= $zoomCooficient;
            $this->zoomed_bounds[$_key]['y2'] *= $zoomCooficient;

            imageline($final, $this->zoomed_bounds[$_key]['x1'], $this->zoomed_bounds[$_key]['y1'], $this->zoomed_bounds[$_key]['x2'], $this->zoomed_bounds[$_key]['y2'], $redColor);
        }

        foreach ($this->zoomed_bounds as $_point){

            //print '<pre>' . print_r($_point, true) . '</pre>';

            imagesetpixel($final, $_point['x1'], $_point['y1'], $blueColor);

        }

        if (in_array('withPerdendicular', $params)){

            foreach ($this->perpendicularityLines as $_line){

                // обнуляем систему координат
                $_line['x1'] -= $zoomOffset['minX'];
                $_line['y1'] -= $zoomOffset['minY'];
                $_line['x2'] -= $zoomOffset['minX'];
                $_line['y2'] -= $zoomOffset['minY'];

                $_line['x1'] *= $zoomCooficient;
                $_line['y1'] *= $zoomCooficient;
                $_line['x2'] *= $zoomCooficient;
                $_line['y2'] *= $zoomCooficient;

                imageline($final, $_line['x1'], $_line['y1'], $_line['x2'], $_line['y2'], $blueColor);

            }

            //print '<pre>' . print_r($this->perpendicularityLines, true) . '</pre>';

        }

        if (in_array('withDot', $params)){

            // обнуляем систему координат
            $this->point_to_check['coords']['x'] -= $zoomOffset['minX'];
            $this->point_to_check['coords']['y'] -= $zoomOffset['minY'];

            $this->point_to_check['coords']['x'] *= $zoomCooficient;
            $this->point_to_check['coords']['y'] *= $zoomCooficient;


            imagesetpixel($final, $this->point_to_check['coords']['x'], $this->point_to_check['coords']['y'], $greenColor);

            //print '<pre>' . print_r($this->perpendicularityLines, true) . '</pre>';

        }

        imagepng($final, $cachedFile);

        print '<img src="data:image/jpeg;base64,' . base64_encode(file_get_contents($cachedFile)) . '">';

        //exit();

    }

    private function calculateBoundsBox(){

        /*
         * calculation $bounds_box
         * lowest x is x1, biggest x is x2
         * lowest y is y1, biggest y is y2
         *
         */

        foreach ($this->polygon_coordinates as $point){

            $x = $point['coords']['x'] ;
            $y = $point['coords']['y'] ;

            if ($x < $this->bounds_box['x1'] OR $this->bounds_box['x1'] == 0)
                $this->bounds_box['x1'] = $x ;

            if ($x > $this->bounds_box['x2'] OR $this->bounds_box['x2'] == 0)
                $this->bounds_box['x2'] = $x ;

            if ($y < $this->bounds_box['y1'] OR $this->bounds_box['y1'] == 0)
                $this->bounds_box['y1'] = $y ;

            if ($y > $this->bounds_box['y2'] OR $this->bounds_box['y2'] == 0)
                $this->bounds_box['y2'] = $y ;

        }

    }

    private function calculatePerpendicularityLines(){

        /*
         *
         * Проводим перпендикуляры от точки до граней boundsBox
         *
         */

        foreach ($this->perpendicularityLines as $_line_key => $list){

            $lineInfo = ['x1' => 0, 'y1' => 0, 'x2' => $this->point_to_check['coords']['x'], 'y2' => $this->point_to_check['coords']['y'], 'crosses_ticks' => 0];

            /*
             * Так как мы проводим перпендикуляры от проверямой точки к коробке полигона (bounds_box),
             * а потом проверяем пересечения получившихся перпендикуляров с гранями полигона, то может возикнуть следующая проблема:
             * если грань полигона полностью перпендикулярна (ровная!) нашему перпендикуляру, то он будет с ней не пересекаться, а соприкасаться и дальнейшая арифметика не работает.
             * Поэтому мы удлиняем каждый перпендикуляр на коэффициент $logerityCoefficient
             */
            $logerityCoefficient = 0.000002;

            if ($_line_key == 'linetop'){

                $lineInfo['x1'] = $this->point_to_check['coords']['x'] ;
                $lineInfo['y1'] = $this->bounds_box['y1'] - $this->bounds_box['y1'] * $logerityCoefficient;

            }

            if ($_line_key == 'linebottom'){

                $lineInfo['x1'] = $this->point_to_check['coords']['x'] ;
                $lineInfo['y1'] = $this->bounds_box['y2'] + $this->bounds_box['y2'] * $logerityCoefficient;

            }

            if ($_line_key == 'lineleft'){

                $lineInfo['y1'] = $this->point_to_check['coords']['y'] ;
                $lineInfo['x1'] = $this->bounds_box['x1'] - $this->bounds_box['x1'] * $logerityCoefficient;

            }

            if ($_line_key == 'lineright'){

                $lineInfo['y1'] = $this->point_to_check['coords']['y'] ;
                $lineInfo['x1'] = $this->bounds_box['x2'] + $this->bounds_box['x2'] * $logerityCoefficient ;

            }

            $this->perpendicularityLines[$_line_key] = $lineInfo;

        }

        //print '<pre>' . print_r($this->perpendicularityLines, true) . '</pre>';

    }

    public function isCrossesWith($lat, $lng){

        $this->point_to_check = [$lat, $lng];

        $this->point_to_check['coords'] = $this->convertLatLngIntoCoords($lat, $lng);


        $this->calculateBoundsBox();

        $this->calculatePerpendicularityLines();

        $isCrosses = false ;

        $this->isInsideBoundsBox = self::isPointInsideBoundsBox($this->point_to_check['coords']['x'], $this->point_to_check['coords']['y'], $this->bounds_box);

        /*
         * Необходимо посчитать количество пересечений перпендикуляров с гранями полигона. (Только если точка находится внутри bounds_box
         */
        if ($this->isInsideBoundsBox)
            foreach ($this->perpendicularityLines as $_line_key => $_perendicular)
                foreach ($this->polygon_bounds as $_point){


                    $isLinesCrosses = self::isLinesCrosses($_perendicular, $_point);

                    if ($isLinesCrosses)
                        $this->perpendicularityLines[$_line_key]['crosses_ticks']++ ;

                    //print '$isLinesCrosses: ' . (int) $isLinesCrosses . '<br/>';


                }

        //if ($this->isInsideBoundsBox)
        //self::previewBounds(['withPerdendicular', 'withDot']);

        //print '<pre>' . print_r($this->polygon_bounds, true) . '</pre>';
        //print '<pre>' . print_r($this->perpendicularityLines, true) . '</pre>';

        /*
         * Point falls into a poligon if
         * 1. all the perpendicularity lines intersects ($_line['crosses_ticks']) with polygon at least 1 time
         * 2. or crosses_ticks is higher than 1 and even in the same time (3, 5, 7 etc)
         *
         * If at least one perpendicularity has different crosses_ticks amount then point doesn't fall into polygon
         */

        if ($isCrosses == false){

            // предполагаем, что точка входит в полигон. Если это не так - сбросим флаг в цикле ниже
            $isCrosses = true ;

            foreach ($this->perpendicularityLines as $_line){

                if ($_line['crosses_ticks'] == 0 OR self::isOddNumber($_line['crosses_ticks']) == false)
                    $isCrosses = false ;

            }

        }

        return $isCrosses ;
    }

    static function isOddNumber($number){

        if ($number % 2 == 0)
            return false ;

        return true ;

    }

    /*
     * Arg: latitude, longitude
     * Result: array with pixels position [x => 0, y => 0]
     */
    public function convertLatLngIntoCoords($lat, $lng){

        $x = ($lng + 180) * ($this->map_width / 360);

        // convert from degrees to radians
        $lng_rad = $lat * M_PI / 180;

        // get y value
        $mercN = log(tan((M_PI / 4) + ($lng_rad / 2 )));

        $y = ($this->map_height / 2) - ($this->map_width * $mercN / (2 * M_PI));

        return ['x' => $x, 'y' => $y];
    }

    /*
     * Does this point is inside the polygon box? (Polygon box is a raw rectangle which contain all the polygon)
     */
    static function isPointInsideBoundsBox($x, $y, $boundsBox){

        if ($x < $boundsBox['x1'] OR $x > $boundsBox['x2'])
            return false ;

        if ($y < $boundsBox['y1'] OR $y > $boundsBox['y2'])
            return false ;

        return true;

    }

    /*
     * Binary operation on two vectors
     */
    static function getVectorCrossProduct($x1, $y1, $x2, $y2){

        $result = $x1 * $y2 - $x2 * $y1;

        return $result ;
    }

    /*
     * Get know does two vectors crosses each other
     * What is P1, P2, P3, P4 vectors? (http://grafika.me/node/237)
     *
     */
    static function isLinesCrosses($line1, $line2){

        /* form vectors (coordinates) */
        $vectorP3P4 = [
            'x' => $line2['x2'] - $line2['x1'],
            'y' => $line2['y2'] - $line2['y1']
        ] ;
        $vectorP1P2 = [
            'x' => $line1['x2'] - $line1['x1'],
            'y' => $line1['y2'] - $line1['y1']
        ] ;

        $vectorP3P1 = [
            'x' => $line1['x1'] - $line2['x1'],
            'y' => $line1['y1'] - $line2['y1']
        ] ;

        $vectorP3P2 = [
            'x' => $line1['x2'] - $line2['x1'],
            'y' => $line1['y2'] - $line2['y1']
        ] ;

        $vectorP1P3 = [
            'x' => $line2['x1'] - $line1['x1'],
            'y' => $line2['y1'] - $line1['y1']
        ] ;

        $vectorP1P4 = [
            'x' => $line2['x2'] - $line1['x1'],
            'y' => $line2['y2'] - $line1['y1']
        ] ;

        $v1 = self::getVectorCrossProduct($vectorP3P4['x'], $vectorP3P4['y'], $vectorP3P1['x'], $vectorP3P1['y']);
        $v2 = self::getVectorCrossProduct($vectorP3P4['x'], $vectorP3P4['y'], $vectorP3P2['x'], $vectorP3P2['y']);

        $v3 = self::getVectorCrossProduct($vectorP1P2['x'], $vectorP1P2['y'], $vectorP1P3['x'], $vectorP1P3['y']);
        $v4 = self::getVectorCrossProduct($vectorP1P2['x'], $vectorP1P2['y'], $vectorP1P4['x'], $vectorP1P4['y']);

        if (self::isLessThenZeroWithPrecision($v1 * $v2) AND self::isLessThenZeroWithPrecision($v3 * $v4))
            return true ;

        return false ;

    }

    static function isLessThenZeroWithPrecision($a){

        $precision = 1e-15 ;
        //print $precision ;

        return 0 - $a > $precision ;

    }

}

?>