<?php

function detectBlurInImage($imagePath) {
    if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
        return false;
    }
    
    try {
        $image = null;
        $mime = mime_content_type($imagePath);
        
        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($imagePath);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($imagePath);
        }
        
        if (!$image) return false;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width < 100 || $height < 100) {
            imagedestroy($image);
            return true; // Image too small
        }
        
        // Convert to grayscale for analysis
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        
        // Sample the center region for blur detection
        $sample_width = (int)min(200, $width / 2);
        $sample_height = (int)min(200, $height / 2);
        $start_x = (int)(($width - $sample_width) / 2);
        $start_y = (int)(($height - $sample_height) / 2);
        
        // Calculate Laplacian variance (higher = sharper, lower = blurrier)
        $laplacian_variance = calculateLaplacianVariance($image, $start_x, $start_y, $sample_width, $sample_height);
        
        imagedestroy($image);
        
        // Threshold: if variance is too low, image is too blurry
        // Threshold of 100 is conservative; adjust based on testing
        return $laplacian_variance < 100;
        
    } catch (Exception $e) {
        error_log("Blur detection error: " . $e->getMessage());
        return false;
    }
}

function calculateLaplacianVariance($image, $start_x, $start_y, $width, $height) {
    $laplacian_kernel = [
        [0, -1, 0],
        [-1, 4, -1],
        [0, -1, 0]
    ];
    
    $sum = 0;
    $count = 0;
    $values = [];
    
    for ($y = $start_y + 1; $y < $start_y + $height - 1; $y += 5) {
        for ($x = $start_x + 1; $x < $start_x + $width - 1; $x += 5) {
            $val = 0;
            
            for ($ky = -1; $ky <= 1; $ky++) {
                for ($kx = -1; $kx <= 1; $kx++) {
                    $rgb = imagecolorat($image, (int)($x + $kx), (int)($y + $ky));
                    $val += (($rgb >> 8) & 0xFF) * $laplacian_kernel[$ky + 1][$kx + 1];
                }
            }
            
            $values[] = $val;
            $sum += $val;
            $count++;
        }
    }
    
    if ($count === 0) return 0;
    
    $mean = $sum / $count;
    $variance = 0;
    
    foreach ($values as $v) {
        $variance += pow($v - $mean, 2);
    }
    
    return $variance / $count;
}
?>
