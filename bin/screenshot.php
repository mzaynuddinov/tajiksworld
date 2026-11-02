<?php
declare(strict_types=1);
$path = dirname(__DIR__) . '/screenshot.png';
$image = imagecreatetruecolor(1200, 900);
$paper = imagecolorallocate($image, 244, 241, 234);
$ink = imagecolorallocate($image, 22, 21, 19);
$crimson = imagecolorallocate($image, 161, 29, 46);
$card = imagecolorallocate($image, 255, 253, 249);
$rule = imagecolorallocate($image, 217, 210, 197);
$muted = imagecolorallocate($image, 94, 88, 79);
imagefilledrectangle($image, 0, 0, 1200, 900, $paper);
imagefilledrectangle($image, 0, 0, 1200, 36, $ink);
imagefilledrectangle($image, 0, 92, 1200, 128, $crimson);
imagestring($image, 5, 48, 52, 'TAJIKSWORLD', $ink);
imagestring($image, 2, 48, 104, 'BREAKING    Mountain roads stay open overnight', $card);
$blocks = [[48, 160, 720, 520], [792, 160, 360, 160], [792, 340, 360, 160], [792, 520, 360, 160]];
foreach ($blocks as [$x, $y, $w, $h]) {
    imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $card);
    imagerectangle($image, $x, $y, $x + $w, $y + $h, $rule);
}
imagestring($image, 5, 72, 190, 'Featured story', $crimson);
imagestring($image, 5, 72, 230, 'A newsroom built to be edited', $ink);
imagestring($image, 3, 72, 270, 'Politics   Economy   Technology   World', $muted);
imagefilledrectangle($image, 48, 700, 1152, 860, $ink);
imagestring($image, 3, 72, 760, 'TajiksWorld  |  tj   ru   en', $card);
imagepng($image, $path);
echo $path, "\n";
