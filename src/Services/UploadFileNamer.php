<?php

namespace App\Services;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Util\Transliterator;

/**
 * Directory namer wich can create subfolder depends on generated filename.
 *
 * @author Konstantin Myakshin <koc-dp@yandex.ru>
 */
class UploadFileNamer implements NamerInterface
{    
    public function name($object, PropertyMapping $mapping): string
    {
        if ($object->getFileName()) return $object->getFileName();
        
        $file = $mapping->getFile($object);
        $name = $file->getClientOriginalName();

        $name = Transliterator::transliterate($name);

        return $name;
    }
}
