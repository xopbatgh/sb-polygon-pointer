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

            if ($_line_key == 'linetop'){

                $lineInfo['x1'] = $this->point_to_check['coords']['x'] ;
                $lineInfo['y1'] = $this->bounds_box['y1'] ;

            }

            if ($_line_key == 'linebottom'){

                $lineInfo['x1'] = $this->point_to_check['coords']['x'] ;
                $lineInfo['y1'] = $this->bounds_box['y2'] ;

            }

            if ($_line_key == 'lineleft'){

                $lineInfo['y1'] = $this->point_to_check['coords']['y'] ;
                $lineInfo['x1'] = $this->bounds_box['x1'] ;

            }

            if ($_line_key == 'lineright'){

                $lineInfo['y1'] = $this->point_to_check['coords']['y'] ;
                $lineInfo['x1'] = $this->bounds_box['x2'] ;

            }

            $this->perpendicularityLines[$_line_key] = $lineInfo;

        }

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


        /*
         * Если все пересечения по 1 - точка входит в область
         */

        if ($isCrosses == false){

            // предполагаем, что точка входит в полигон. Если это не так - сбросим флаг в цикле ниже
            $isCrosses = true ;

            foreach ($this->perpendicularityLines as $_line){

                if ($_line['crosses_ticks'] == 0 AND self::isOddNumber($_line['crosses_ticks']) == false)
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