<?php
session_start();
const SYMBOLS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const LENGTH = 6;
const WIDTH = 120;
const HEIGHT = 40;
const FONT_SIZE = 14;
$font = dirname(__FILE__) . "/res/fonts/comic.ttf";
header("Content-type: image/png");
putenv("GDFONTPATH=" . realpath("."));
$image = imagecreatetruecolor(WIDTH, HEIGHT);
imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
$captcha = "";
for ($i = 0; $i < LENGTH; $i++) {
    $captcha .= substr(SYMBOLS, rand(0, strlen(SYMBOLS) - 1), 1);
    $x = (WIDTH - 20) / LENGTH * $i + 10;
    $x = rand((int)$x, (int)$x + 4);
    $y = HEIGHT - (HEIGHT - FONT_SIZE) / 2;
    $color = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
    $angle = rand(-25, 25);
    imagettftext($image, FONT_SIZE, $angle, $x, $y, $color, $font, $captcha[$i]);
}
$_SESSION["captcha"] = $captcha;
imagepng($image);
imagedestroy($image);
