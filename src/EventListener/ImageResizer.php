<?php

namespace App\EventListener;

use App\Document\ConfImage;
use App\Document\ElementImage;
use Intervention\Image\ImageManagerStatic as InterventionImage;

/* While image is uploaded, generate thumbnails and other thing */
class ImageResizer
{
    protected $image_resize_width;

    public function __construct($image_resize_width)
    {
        if (isset($image_resize_width)) { // strange bug in localhost this variable is not defined
            $this->image_resize_width = $image_resize_width;
        } else {
            $this->image_resize_width = 700;
        }
    }

    public function postPersist(\Doctrine\ODM\MongoDB\Event\LifecycleEventArgs $args)
    {
        $document = $args->getDocument();
        if ($document instanceof ConfImage && !$document->isExternalFile()) {
            $w = 512;
            $h = 512;
            $srcImage = $document->calculateFilePath();
            $destImage = preg_replace('/(\.jpe?g|\.png)$/i', '-'.$w.'x'.$h.'.png', $srcImage);
            $image = InterventionImage::make($srcImage)->fit($w, $h)->save($destImage);
        } elseif ($document instanceof ElementImage) {
            if (!$document->getFile()) {
                return;
            }
            $srcImage = $document->calculateFilePath();
            $image = InterventionImage::make($srcImage);
            if ($image->width() > $this->image_resize_width) {
                $image->widen($this->image_resize_width)->save($srcImage);
            }

            return;
        }
    }
}
