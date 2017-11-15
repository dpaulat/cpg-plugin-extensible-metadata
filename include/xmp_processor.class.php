<?php
/**************************************************
  Coppermine Plugin - Extensible Metadata
 *************************************************
  Copyright (c) 2017 Dan Paulat
 *************************************************
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 3
  as published by the Free Software Foundation.
 ***************************************************/

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

class XmpProcessor
{
    const XMP_EXT = ".xmp";

    private $imagePath;
    private $sidecarPath;

    public function __construct($filepath, $filename)
    {
        global $CONFIG;
        $info = pathinfo($filename);
        $this->imagePath = $CONFIG['fullpath'] . $filepath . $filename;
        $this->sidecarPath = $CONFIG['fullpath'] . $filepath . $info['filename'] . self::XMP_EXT;
    }

    public function imageExists()
    {
        return file_exists($this->imagePath);
    }

    public function sidecarExists()
    {
        return file_exists($this->sidecarPath);
    }

    public function generateSidecar($overwrite = false)
    {
        $data = null;

        if (($overwrite || !$this->sidecarExists()) && $this->imageExists()) {
            $data = $this->getBasicXmpData($this->imagePath);

            if ($data !== false) {
                $this->writeSidecar($data);
            }
        }

        return $data;
    }

    public function readSidecar()
    {
        return file_get_contents($this->sidecarPath);
    }

    private function writeSidecar($data)
    {
        return file_put_contents($this->sidecarPath, $data);
    }

    private function getBasicXmpData($filename, $chunk_size = 1024)
    {
        if (!is_int($chunk_size)) {
            // Expected integer value for argument #2 (chunkSize)
            return false;
        }

        if ($chunk_size < 12) {
            // Chunk size cannot be less than 12 argument #2 (chunkSize)
            return false;
        }

        if (($file_pointer = fopen($filename, 'rb')) === false) {
            // Could not open file for reading
            return false;
        }

        $tag = '<x:xmpmeta';
        $buffer = false;

        // find open tag
        while ($buffer === false && ($chunk = fread($file_pointer, $chunk_size)) !== false) {
            if (strlen($chunk) <= 10) {
                break;
            }
            if (($position = strpos($chunk, $tag)) === false) {
                // if open tag not found, back up just in case the open tag is on the split.
                fseek($file_pointer, -10, SEEK_CUR);
            } else {
                $buffer = substr($chunk, $position);
            }
        }

        if ($buffer === false) {
            fclose($file_pointer);
            return false;
        }

        $tag = '</x:xmpmeta>';
        $offset = 0;
        while (($position = strpos($buffer, $tag, $offset)) === false
            && ($chunk = fread($file_pointer, $chunk_size)) !== false
            && !empty($chunk)) {
            $offset = strlen($buffer) - 12; // subtract the tag size just in case it's split between chunks.
            $buffer .= $chunk;
        }

        fclose($file_pointer);

        if ($position === false) {
            // this would mean the open tag was found, but the close tag was not.  Maybe file corruption?
            // No close tag found.  Possibly corrupted file.
            return false;
        } else {
            $buffer = substr($buffer, 0, $position + 12);
        }

        return $buffer;
    }
}
