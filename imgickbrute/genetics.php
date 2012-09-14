<?php
/**
 *
 * Image-Matching using phpGD brute force
 *
 * @auther Mike Bernat <mike@mikebernat.com>
 */


error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
set_time_limit(0);

$config = array(
    'width'          => 480,
    'height'         => 200,
    'outDir'         => '../output/imgickbrute/',
    'compare'        => '../wrigley_field.jpg',
    'iterations'     => 100000,
    'dumpImageEvery' => 500,
);

// Setup logging and output
$logDir = $config['outDir'] . 'log' . time();
mkdir($logDir, 0777, 1);
$logFile = fopen($logDir . '/data.log', 'w+') or exit('No access to log directory ' . $logDir . '/data.log');
// Grab the control image for comparison
$controlImage = new Imagick($config['compare']);

// Start with a blank slate - image with a white background
$newImage = new Imagick;
$newImage->newimage($config['width'], $config['height'], new ImagickPixel('white'));
$newImage->setImageFormat('jpg');

$start = time();

$difference = PHP_INT_MAX;


for ($iterationCount=0; $iterationCount<$config['iterations'];$iterationCount++) {
    // Create our candidate by copying the last best-match
    $testImage = clone $newImage;
    $draw = new ImagickDraw;


    // Come up with some dimensions for the shape to draw
    $x1 = rand(0, $config['width']);
    $x2 = rand($x1, $config['width']);
    $y1 = rand(0, $config['height']);
    $y2 = rand($y1, $config['height']);

    $color = new ImagickPixel();
    $rgba = 'rgba('.
            rand(0, 255) . ',' .
            rand(0, 255) . ',' .
            rand(0, 255) . ',' .
            (rand(0, 100) / 100) . ')';

    $color->setColor($rgba);
    $draw->setFillColor($color);
    $draw->rectangle($x1, $y1, $x2, $y2);
    $testImage->drawimage($draw);

    $newDifference = $controlImage->compareimages($testImage, Imagick::METRIC_MEANSQUAREERROR);
    $newDifference = $newDifference[1];
    fwrite($logFile,
            'itr='.$iterationCount . ' ' .
            'duration='. (time() - $start) . 's ' .
            'difference='. $difference . (($newDifference < $difference) ? '+' : '-').
            'memory='.memory_get_peak_usage(true) . 'kb'.
            PHP_EOL);

    if ($newDifference < $difference) {
        // The candidate is deemed an improvement
        // Update the difference value and best-match image
        $difference = $newDifference;
        $newImage->destroy();
        $newImage = clone $testImage;
    }

    if ($iterationCount % $config['dumpImageEvery'] == 0) {
        $newImage->writeImage($logDir . '/' . $iterationCount . '.jpg');
    }

    $testImage->destroy();
}

exit(PHP_EOL . 'FIN' . PHP_EOL);