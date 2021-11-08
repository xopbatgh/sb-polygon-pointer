<?php

$currentDir = __DIR__ . '/';

include_once $currentDir . 'sbPolyPointer.php';


$polygonBox = [
    [55.761515, 37.600375],
    [55.759428, 37.651156],
    [55.737112, 37.649566],
    [55.737649, 37.597301],
];

$sbPolygonEngine = new sbPolygonEngine($polygonBox);

// check if point is inside the polygon
$isCrosses = $sbPolygonEngine->isCrossesWith(55.746768, 37.625605);

// draw img with visualization
$sbPolygonEngine->previewBounds($draw_perpendicular = true, $draw_center_dot = true);

print '<br/>$isCrosses: ' . (int) $isCrosses . '<br/>';

// dump internal class data
print '<pre>' . print_r($sbPolygonEngine, true) . '</pre>';

?>