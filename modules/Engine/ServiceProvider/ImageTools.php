<?php

namespace App\Engine\ServiceProvider;


class ImageTools
{

    const BASE_PRODUCT_WIDTH = 800;

    public function resize($img, $newWidth = 800, $typeImage = 'product'){

        $imgOriginSize = getimagesize($img);

        $OldWidth = $imgOriginSize[0];
        $OldHeight = $imgOriginSize[1];



        $newHeight = $OldHeight * $newWidth / $OldWidth;

        $imagick = new \Imagick($img);
        $imagick->setImageResolution(50, 50);
        $imagick->setCompression(100);
        $imagick->setOption('png:compression-level', 9);
        $imagick->removeImageProfile('icc');

        $imagick->setImageCompression($imagick::COMPRESSION_ZIP);
        $imagick->setImageCompressionQuality(40);

        $imagick->quantizeImage(50, $imagick::COLORSPACE_SRGB , 256, true, false);
        if ($typeImage == 'product' && $OldWidth < self::BASE_PRODUCT_WIDTH){
            $imagick->writeImage($img );
        }else{
            $imagick->resizeImage($newWidth, $newHeight, $imagick::DISPOSE_UNDEFINED, 1);
        }


        $imagick->writeImage($img );

        return $img;

    }

}