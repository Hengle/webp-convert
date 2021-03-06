<?php

namespace WebPConvert\Converters;

use WebPConvert\Converters\Exceptions\ConverterNotOperationalException;
use WebPConvert\Converters\Exceptions\ConverterFailedException;
use WebPConvert\Converters\Exceptions\ConversionDeclinedException;

class Gd
{
    public static function convert($source, $destination, $options = [], $prepareDestinationFolder = true)
    {
        if ($prepareDestinationFolder) {
            ConverterHelper::prepareDestinationFolderAndRunCommonValidations($source, $destination);
        }

        $defaultOptions = array_merge(ConverterHelper::$defaultOptions, [
            'skip-pngs' => true,
        ]);
        $options = array_merge($defaultOptions, $options);

        if (!extension_loaded('gd')) {
            throw new ConverterNotOperationalException('Required GD extension is not available.');
        }

        if (!function_exists('imagewebp')) {
            throw new ConverterNotOperationalException('Required imagewebp() function is not available.');
        }

        switch (ConverterHelper::getExtension($source)) {
            case 'png':
                if (!$options['skip-pngs']) {
                    if (!function_exists('imagecreatefrompng')) {
                        throw new ConverterNotOperationalException('Required imagecreatefrompng() function is not available.');
                    }
                    $image = imagecreatefrompng($source);
                    if (!$image) {
                        throw new ConverterFailedException('imagecreatefrompng("' . $source . '") failed');
                    }
                } else {
                    throw new ConversionDeclinedException('PNG file skipped. GD is configured not to convert PNGs');
                }
                break;
            default:
                if (!function_exists('imagecreatefromjpeg')) {
                    throw new ConverterNotOperationalException('Required imagecreatefromjpeg() function is not available.');
                }
                $image = imagecreatefromjpeg($source);
                if (!$image) {
                    throw new ConverterFailedException('imagecreatefromjpeg("' . $source . '") failed');
                }
        }

        // Checks if either imagecreatefromjpeg() or imagecreatefrompng() returned false

        $success = imagewebp($image, $destination, $options['quality']);

        if (!$success) {
            throw new ConverterFailedException('Call to imagewebp() failed. Probably failed writing file');
        }

        /*
         * This hack solves an `imagewebp` bug
         * See https://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files
         *
         */

        if (filesize($destination) % 2 == 1) {
            file_put_contents($destination, "\0", FILE_APPEND);
        }

        imagedestroy($image);
    }
}
