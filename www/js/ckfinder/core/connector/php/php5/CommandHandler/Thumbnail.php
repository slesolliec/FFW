<?php
/*
 * CKFinder
 * ========
 * http://www.ckfinder.com
 * Copyright (C) 2007-2008 Frederico Caldeira Knabben (FredCK.com)
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */

/**
 * @package CKFinder
 * @subpackage CommandHandlers
 * @copyright Frederico Caldeira Knabben
 */

/**
 * Handle Thumbnail command (create thumbnail if doesn't exist)
 * 
 * @package CKFinder
 * @subpackage CommandHandlers
 * @copyright Frederico Caldeira Knabben
 */
class CKFinder_Connector_CommandHandler_Thumbnail extends CKFinder_Connector_CommandHandler_CommandHandlerBase
{
    /**
     * Command name
     *
     * @access private
     * @var string
     */
    private $command = "Thumbnail";

    /**
     * handle request and send response
     * @access public
     *
     */
    public function sendResponse()
    {
        @ob_end_clean();
        header("Content-Encoding: none");
        
        $this->checkConnector();

        $_config =& CKFinder_Connector_Core_Factory::getInstance("Core_Config");

        $_thumbnails = $_config->getThumbnailsConfig();
        if (!$_thumbnails->getIsEnabled()) {
            header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_THUMBNAILS_DISABLED);
            header("HTTP/1.0 403 Forbidden");
            exit;
        }

        if (!$this->_currentFolder->checkAcl(CKFINDER_CONNECTOR_ACL_FILE_VIEW)) {
            header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
            header("HTTP/1.0 403 Forbidden");
            exit;
        }

        if (!isset($_GET["FileName"])) {
            header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
            header("HTTP/1.0 404 Not Found");
            exit;
        }
        
        $fileName = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_GET["FileName"]);
        $_resourceTypeInfo = $this->_currentFolder->getResourceTypeConfig();
        
        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName)) {
            header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
            header("HTTP/1.0 403 Forbidden");
            exit;
        }
        
        $sourceFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
        
        if ($_resourceTypeInfo->checkIsHiddenFile($fileName) || !file_exists($sourceFilePath)) {
            header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_FILE_NOT_FOUND);
            header("HTTP/1.0 404 Not Found");
            exit;
        }        
        
        $thumbFilePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getThumbsServerPath(), $fileName);

        // If the thumbnail file doesn't exists, create it now.
        if (!file_exists($thumbFilePath)) {
            if(!$this->createThumb($sourceFilePath, $thumbFilePath, $_thumbnails->getMaxWidth(), $_thumbnails->getMaxHeight(), $_thumbnails->getQuality(), true)) {
                header("X-CKFinder-Error: ".CKFINDER_CONNECTOR_ERROR_ACCESS_DENIED);
                header("HTTP/1.0 500 Internal Server Error");
                exit;                
            }
        }

        $size = filesize($thumbFilePath);
        $sourceImageAttr = getimagesize($thumbFilePath);
        $mime = $sourceImageAttr["mime"];

        $rtime = isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])?@strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]):0;
        $mtime =  filemtime($thumbFilePath);

        if($rtime>=$mtime) {
            header("HTTP/1.0 304 Not Modified");
            exit();
        }

        //header("Cache-Control: cache, must-revalidate");
        //header("Pragma: public");
        //header("Expires: 0");
        header('Cache-control: public');
        header("Content-type: " . $mime . "; name=\"" . basename($thumbFilePath) . "\"");
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', $mtime) . " GMT");
        //header("Content-type: application/octet-stream; name=\"{$file}\"");
        //header("Content-Disposition: attachment; filename=\"{$file}\"");
        header("Content-Length: ".$size);
        readfile($thumbFilePath);
        exit;
    }

    /**
     * Create thumbnail
     *
     * @param string $sourceFile
     * @param string $targetFile
     * @param int $maxWidth
     * @param int $maxHeight
     * @param boolean $preserverAspectRatio
     * @return boolean
     * @static
     * @access public 
     */
    public static function createThumb($sourceFile, $targetFile, $maxWidth, $maxHeight, $quality, $preserverAspectRatio)
    {
        $sourceImageAttr = @getimagesize($sourceFile);
        if ($sourceImageAttr === false) {
            return false;
        }
        $sourceImageWidth = isset($sourceImageAttr[0]) ? $sourceImageAttr[0] : 0;
        $sourceImageHeight = isset($sourceImageAttr[1]) ? $sourceImageAttr[1] : 0;
        $sourceImageMime = isset($sourceImageAttr["mime"]) ? $sourceImageAttr["mime"] : "";
        $sourceImageBits = isset($sourceImageAttr["bits"]) ? $sourceImageAttr["bits"] : 8;
        $sourceImageChannels = isset($sourceImageAttr["channels"]) ? $sourceImageAttr["channels"] : 3;

        if (!$sourceImageWidth || !$sourceImageHeight || !$sourceImageMime) {
            return false;
        }

        $iFinalWidth = $maxWidth == 0 ? $sourceImageWidth : $maxWidth;
        $iFinalHeight = $maxHeight == 0 ? $sourceImageHeight : $maxHeight;

        if ($sourceImageWidth <= $iFinalWidth && $sourceImageHeight <= $iFinalHeight) {
            if ($sourceFile != $targetFile) {
                copy($sourceFile, $targetFile);
            }
            return true;
        }

        if ($preserverAspectRatio)
        {
            // Gets the best size for aspect ratio resampling
            $oSize = CKFinder_Connector_CommandHandler_Thumbnail::GetAspectRatioSize($iFinalWidth, $iFinalHeight, $sourceImageWidth, $sourceImageHeight );
        }
        else {
            $oSize = array($iFinalWidth, $iFinalHeight);
        }

        CKFinder_Connector_Utils_Misc::setMemoryForImage($sourceImageWidth, $sourceImageHeight, $sourceImageBits, $sourceImageChannels);

        switch ($sourceImageAttr['mime'])
        {
            case 'image/gif':
                {
                    if (@imagetypes() & IMG_GIF) {
                        $oImage = @imagecreatefromgif($sourceFile);
                    } else {
                        $ermsg = 'GIF images are not supported';
                    }
                }
                break;
            case 'image/jpeg':
                {
                    if (@imagetypes() & IMG_JPG) {
                        $oImage = @imagecreatefromjpeg($sourceFile) ;
                    } else {
                        $ermsg = 'JPEG images are not supported';
                    }
                }
                break;
            case 'image/png':
                {
                    if (@imagetypes() & IMG_PNG) {
                        $oImage = @imagecreatefrompng($sourceFile) ;
                    } else {
                        $ermsg = 'PNG images are not supported';
                    }
                }
                break;
            case 'image/wbmp':
                {
                    if (@imagetypes() & IMG_WBMP) {
                        $oImage = @imagecreatefromwbmp($sourceFile);
                    } else {
                        $ermsg = 'WBMP images are not supported';
                    }
                }
                break;
            default:
                $ermsg = $sourceImageAttr['mime'].' images are not supported';
                break;
        }

        if (isset($ermsg) || false === $oImage) {
            return false;
        }


        $oThumbImage = imagecreatetruecolor($oSize["Width"], $oSize["Height"]);
        //imagecopyresampled($oThumbImage, $oImage, 0, 0, 0, 0, $oSize["Width"], $oSize["Height"], $sourceImageWidth, $sourceImageHeight);
        CKFinder_Connector_Utils_Misc::fastImageCopyResampled($oThumbImage, $oImage, 0, 0, 0, 0, $oSize["Width"], $oSize["Height"], $sourceImageWidth, $sourceImageHeight, (int)max(floor($quality/20), 1));

        switch ($sourceImageAttr['mime'])
        {
            case 'image/gif':
                imagegif($oThumbImage, $targetFile);
                break;
            case 'image/jpeg':
                imagejpeg($oThumbImage, $targetFile, $quality);
                break;
            case 'image/png':
                imagepng($oThumbImage, $targetFile);
                break;
            case 'image/wbmp':
                imagewbmp($oThumbImage, $targetFile);
                break;
        }

        $_config =& CKFinder_Connector_Core_Factory::getInstance("Core_Config");
        if (file_exists($targetFile) && ($perms = $_config->getChmodFiles())) {
            $oldUmask = umask(0);
            chmod($targetFile, $perms);
            umask($oldUmask);
        }
        
        imageDestroy($oImage);
        imageDestroy($oThumbImage);

        return true;
    }



    /**
     * Return aspect ratio size, returns associative array:
     * <pre>
     * Array
     * (
     *      [Width] => 80
     *      [Heigth] => 120
     * )
     * </pre>
     *
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $actualWidth
     * @param int $actualHeight
     * @return array
     * @static
     * @access public 
     */
    public static function getAspectRatioSize($maxWidth, $maxHeight, $actualWidth, $actualHeight)
    {
        $oSize = array("Width"=>$maxWidth, "Height"=>$maxHeight);

        // Calculates the X and Y resize factors
        $iFactorX = (float)$maxWidth / (float)$actualWidth;
        $iFactorY = (float)$maxHeight / (float)$actualHeight;

        // If some dimension have to be scaled
        if ($iFactorX != 1 || $iFactorY != 1)
        {
            // Uses the lower Factor to scale the oposite size
            if ($iFactorX < $iFactorY) {
                $oSize["Height"] = (int)round($actualHeight * $iFactorX);
            }
            else if ($iFactorX > $iFactorY) {
                $oSize["Width"] = (int)round($actualWidth * $iFactorY);
            }
        }

        if ($oSize["Height"] <= 0) {
            $oSize["Height"] = 1;
        }
        if ($oSize["Width"] <= 0) {
            $oSize["Width"] = 1;
        }

        // Returns the Size
        return $oSize;
    }
}
