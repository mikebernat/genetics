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
    'outDir'         => '../output/gdbrute/',
    'compare'        => '../wrigley_field.jpg',
    'iterations'     => 10000000,
    'dumpImageEvery' => 500,
);

// Setup logging and output
$logDir = $config['outDir'] . 'log' . time();
mkdir($logDir, 0777, 1);
$logFile = fopen($logDir . '/data.log', 'w+') or exit('No access to log directory ' . $logDir . '/data.log');

// Grab the control image for comparison
$controlImage = imagecreatefromjpeg($config['compare']);

// Start with a blank slate
$newImage = imagecreatetruecolor($config['width'], $config['height']);
$white = imagecolorallocate($newImage, 255, 255, 255);
imagefill($newImage, 0, 0, $white);

$start = time();

$difference = PHP_INT_MAX; // something biggish
for ($iterationCount=0; $iterationCount<$config['iterations'];$iterationCount++) {

    // Create our candidate by copying the last best-match
    $testImage = imagecreatetruecolor($config['width'], $config['height']);
    imagecopy($testImage, $newImage, 0, 0, 0, 0, $config['width'], $config['height']);

    // Come up with some dimensions for the shape to draw
    // The loop is my attempt at gradually forcing smaller
    // shapes as the image gets closer and closer to the control
    do {
        $x1 = rand(0, $config['width']);
        $x2 = rand($x1, $config['width']);
        $y1 = rand(0, $config['height']);
        $y2 = rand($y1, $config['height']);

        $absX = abs($x1 - $x2);
        $absY = abs($y1 - $y2);

        print ($absX + $absY) .'<'. (sqrt($difference)/7) . PHP_EOL;

    } while(($absX + $absY) > (sqrt($difference)/7)); // Lets keep sensible sizes

    // Draw the shape on the candidate image
    $color = imagecolorallocatealpha($testImage, rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 127));
    imagefilledrectangle($testImage, $x1, $y1, $x2, $y2, $color);

    $newDifference = compare($controlImage, $testImage);
    fwrite($logFile,
            'itr='.$iterationCount . ' ' .
            'duration='. (time() - $start) . 's ' .
            'difference='. $difference . (($newDifference < $difference) ? '+' : '-').
            'memory='.memory_get_peak_usage(true) . 'kb'.
            PHP_EOL);

    if ($newDifference < $difference) {
        // The candidate was deemed an improvement
        $difference = $newDifference;
        $newImage = imagecreatetruecolor($config['width'], $config['height']);
        imagecopy($newImage, $testImage, 0, 0, 0, 0, $config['width'], $config['height']);
    }

    if ($iterationCount % $config['dumpImageEvery'] == 0) {
        imagejpeg($newImage, $logDir . '/' . $iterationCount . '.jpg');
    }

    imagedestroy($testImage);

}

exit(PHP_EOL . 'FIN' . PHP_EOL);


/**
 * Computes the total difference in RGBA for every pixel
 * @param resource $leftImage
 * @param resource $rightImage
 * @return int Difference
 */
function compare($leftImage, $rightImage)
{
    $width = imagesx($leftImage);
    $height = imagesy($leftImage);

    $difference = 0;

    for ($x=0; $x<$width; $x++) {
        for ($y=0; $y<$height; $y++) {
            $leftColors  = imagecolorsforindex($leftImage, imagecolorat($leftImage, $x, $y));
            $rightColors = imagecolorsforindex($rightImage, imagecolorat($rightImage, $x, $y));

            $difference += abs(($leftColors['red'] - $rightColors['red']))
                + abs(($leftColors['green'] - $rightColors['green']))
                + abs(($leftColors['blue'] - $rightColors['blue']))
                + abs(($leftColors['alpha'] - $rightColors['alpha']));
        }
    }

    return abs($difference);
}