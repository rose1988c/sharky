<?php

function ws_crop_image($src, $dist, $x, $y, $w, $h) {/*{{{*/

}/*}}}*/


function ws_resize_image($src, $dist, $m, $crop = false, $strip = false, $quality = false) {/*{{{*/
    $im = false;

    if (is_object($src) && get_class($src) == 'Imagick')
        $im = $src;
    elseif (is_string($src)) {
        $im = new Imagick($src);
        if (!$im) throw new Exception('不支持的文件类型');
    }
    if (!$im) throw new Exception('参数类型错误');

    if ($im->getNumberImages() > 1)
        $im = $im->coalesceImages();

    if ($crop) {
        $im->cropThumbnailImage($m, $m);
    } else {
        $image_w = $im->getImageWidth();
        $image_h = $im->getImageHeight();

        list($new_w, $new_h) = ws_resize_calculate_dimensions($image_w, $image_h, $m, false);

        if ($new_w < $image_w || $new_h < $image_h) {
            //Scale the image
            $im->resizeImage($new_w, $new_h, Imagick::FILTER_POINT, 1);
        }
    }

    if ($strip) $im->stripImage();
    if ($quality) $im->setCompressionQuality($quality);

    //$im->setImageFormat('JPEG');
    $im->setImageColorspace(imagick::COLORSPACE_RGB);
    //Write the new image to a file
    $im->writeImage($dist);

    return true;
}/*}}}*/


function ws_resize_calculate_dimensions($w, $h, $m, $crop = false) {/*{{{*/
    if ($m < 0 || ($w < $m && $h < $m && !$crop))
        return array($w, $h);

    $tw = $th = $m;
    $thumbratio = $tw / $th;
    $imgratio = $w / $h;

    if ($crop) {
        if ($thumbratio < $imgratio)
            $tw = round($th * $imgratio);
        else
            $th = round($tw / $imgratio);
    } else {
        if ($thumbratio < $imgratio)
            $th = round($tw / $imgratio);
        else
            $tw = round($th * $imgratio);
    }
    return array(($tw == 0 ? 1 : $tw), ($th == 0 ? 1 : $th));
}/*}}}*/
?>
