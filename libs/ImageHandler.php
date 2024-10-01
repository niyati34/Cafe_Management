<?php
/**
 * Image Handler for Food Chef Cafe Management System
 * Manages image uploads, resizing, and optimization
 */

class ImageHandler {
    
    private $uploadPath;
    private $allowedTypes;
    private $maxFileSize;
    private $quality;
    
    public function __construct($config = []) {
        $this->uploadPath = $config['upload_path'] ?? 'uploads/';
        $this->allowedTypes = $config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif'];
        $this->maxFileSize = $config['max_file_size'] ?? 5242880; // 5MB
        $this->quality = $config['quality'] ?? 85;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload and process image
     * @param array $file
     * @param string $category
     * @param array $options
     * @return array
     */
    public function uploadImage($file, $category = 'general', $options = []) {
        $result = [
            'success' => false,
            'message' => '',
            'filename' => '',
            'path' => ''
        ];
        
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            $result['message'] = $validation['message'];
            return $result;
        }
        
        // Create category directory
        $categoryPath = $this->uploadPath . $category . '/';
        if (!is_dir($categoryPath)) {
            mkdir($categoryPath, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $this->generateFilename($category, $extension);
        $filepath = $categoryPath . $filename;
        
        // Process and save image
        if ($this->processImage($file['tmp_name'], $filepath, $options)) {
            $result['success'] = true;
            $result['filename'] = $filename;
            $result['path'] = $filepath;
            $result['message'] = 'Image uploaded successfully';
        } else {
            $result['message'] = 'Failed to process image';
        }
        
        return $result;
    }
    
    /**
     * Validate uploaded file
     * @param array $file
     * @return array
     */
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload failed'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'message' => 'File size exceeds limit'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Generate unique filename
     * @param string $category
     * @param string $extension
     * @return string
     */
    private function generateFilename($category, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "{$category}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Process and save image
     * @param string $source
     * @param string $destination
     * @param array $options
     * @return bool
     */
    private function processImage($source, $destination, $options = []) {
        $maxWidth = $options['max_width'] ?? 1200;
        $maxHeight = $options['max_height'] ?? 800;
        $createThumbnail = $options['create_thumbnail'] ?? true;
        $thumbnailSize = $options['thumbnail_size'] ?? 300;
        
        // Get image info
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            return false;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Create image resource
        $image = $this->createImageResource($source, $type);
        if (!$image) {
            return false;
        }
        
        // Resize if needed
        if ($width > $maxWidth || $height > $maxHeight) {
            $image = $this->resizeImage($image, $width, $height, $maxWidth, $maxHeight);
        }
        
        // Save main image
        $saved = $this->saveImage($image, $destination, $type);
        
        // Create thumbnail
        if ($createThumbnail && $saved) {
            $this->createThumbnail($destination, $thumbnailSize);
        }
        
        imagedestroy($image);
        return $saved;
    }
    
    /**
     * Create image resource
     * @param string $source
     * @param int $type
     * @return resource|false
     */
    private function createImageResource($source, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($source);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($source);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($source);
            default:
                return false;
        }
    }
    
    /**
     * Resize image maintaining aspect ratio
     * @param resource $image
     * @param int $width
     * @param int $height
     * @param int $maxWidth
     * @param int $maxHeight
     * @return resource
     */
    private function resizeImage($image, $width, $height, $maxWidth, $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefill($resized, 0, 0, $transparent);
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        return $resized;
    }
    
    /**
     * Save image to file
     * @param resource $image
     * @param string $destination
     * @param int $type
     * @return bool
     */
    private function saveImage($image, $destination, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $destination, $this->quality);
            case IMAGETYPE_PNG:
                return imagepng($image, $destination, round($this->quality / 10));
            case IMAGETYPE_GIF:
                return imagegif($image, $destination);
            default:
                return false;
        }
    }
    
    /**
     * Create thumbnail
     * @param string $sourcePath
     * @param int $size
     * @return bool
     */
    private function createThumbnail($sourcePath, $size) {
        $pathInfo = pathinfo($sourcePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $type = $imageInfo[2];
        $image = $this->createImageResource($sourcePath, $type);
        if (!$image) {
            return false;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create square thumbnail
        $thumbnail = imagecreatetruecolor($size, $size);
        
        // Preserve transparency
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
        
        // Calculate crop dimensions
        $cropSize = min($width, $height);
        $cropX = ($width - $cropSize) / 2;
        $cropY = ($height - $cropSize) / 2;
        
        imagecopyresampled($thumbnail, $image, 0, 0, $cropX, $cropY, $size, $size, $cropSize, $cropSize);
        
        $saved = $this->saveImage($thumbnail, $thumbnailPath, $type);
        
        imagedestroy($image);
        imagedestroy($thumbnail);
        
        return $saved;
    }
    
    /**
     * Delete image and thumbnail
     * @param string $imagePath
     * @return bool
     */
    public function deleteImage($imagePath) {
        $deleted = false;
        
        if (file_exists($imagePath)) {
            $deleted = unlink($imagePath);
        }
        
        // Delete thumbnail if exists
        $pathInfo = pathinfo($imagePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        return $deleted;
    }
    
    /**
     * Get image dimensions
     * @param string $imagePath
     * @return array|false
     */
    public function getImageDimensions($imagePath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime']
        ];
    }
    
    /**
     * Optimize image quality
     * @param string $imagePath
     * @param int $quality
     * @return bool
     */
    public function optimizeImage($imagePath, $quality = null) {
        if ($quality === null) {
            $quality = $this->quality;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $type = $imageInfo[2];
        $image = $this->createImageResource($imagePath, $type);
        if (!$image) {
            return false;
        }
        
        $saved = $this->saveImage($image, $imagePath, $type);
        
        imagedestroy($image);
        return $saved;
    }
}
?>
