<?php
// Simple placeholder image generator
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');

$width = isset($_GET['w']) ? (int)$_GET['w'] : 300;
$height = isset($_GET['h']) ? (int)$_GET['h'] : 200;
$text = isset($_GET['text']) ? htmlspecialchars($_GET['text']) : 'No Image';

echo '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
  <rect width="100%" height="100%" fill="#f3f4f6"/>
  <rect x="10" y="10" width="' . ($width - 20) . '" height="' . ($height - 20) . '" fill="none" stroke="#d1d5db" stroke-width="2" stroke-dasharray="5,5"/>
  <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="16" fill="#6b7280" text-anchor="middle" dominant-baseline="middle">
    <tspan x="50%" dy="-10">📦</tspan>
    <tspan x="50%" dy="25">' . $text . '</tspan>
  </text>
</svg>';
?> 