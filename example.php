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

$isCrosses = $sbPolygonEngine->isCrossesWith(55.746768, 37.625605);

print '$isCrosses: ' . (int) $isCrosses . '<br/>';

$isCrosses = $sbPolygonEngine->isCrossesWith(55.757139, 37.603484);

print '$isCrosses: ' . (int) $isCrosses . '<br/>';

print '<pre>' . print_r($sbPolygonEngine, true) . '</pre>';

?>