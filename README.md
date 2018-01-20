# sb-polygon-pointer
Simple PHP class that provides tools to define is a point (latitude/longitude) is inside the polygon 

See the example at example.php

#### Init custom polygon ####
```PHP

$polygonBox = [
    [55.761515, 37.600375],
    [55.759428, 37.651156],
    [55.737112, 37.649566],
    [55.737649, 37.597301],
];

$sbPolygonEngine = new sbPolygonEngine($polygonBox);

```

#### Checking if point is inside polygon ####

```PHP

$isCrosses = $sbPolygonEngine->isCrossesWith(55.746768, 37.625605);

// $isCrosses is boolean

```

Feel free to contribute
