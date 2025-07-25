<?php
// Create image sizes
$sizes = [
    'logo-96.png' => 96,
    'logo-192.png' => 192,
    'logo-512.png' => 512
];

foreach ($sizes as $filename => $size) {
    // Create image
    $image = imagecreatetruecolor($size, $size);
    
    // Colors
    $blue = imagecolorallocate($image, 44, 90, 160); // #2c5aa0
    $white = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefill($image, 0, 0, $blue);
    
    // Add text
    $text = "BM";
    $font = 5; // Built-in font
    
    // Center text (approximate)
    $x = $size * 0.3;
    $y = $size * 0.4;
    
    // Draw text
    imagestring($image, $font, $x, $y, $text, $white);
    
    // Save image
    imagepng($image, "assets/images/" . $filename);
    imagedestroy($image);
}

echo "Logo images generated successfully!";
?> 