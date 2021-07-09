<?php

namespace App\Services;

use App\Services\DocumentManagerFactory;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Directory namer wich can create subfolder depends on generated filename.
 *
 * @author Konstantin Myakshin <koc-dp@yandex.ru>
 */
class UploadDirectoryNamer implements DirectoryNamerInterface
{
    private $BASE_PATH = 'uploads/';

    private $PATHS = [
      'image' => '/images',
      'element_image' => '/images/elements',
      'element_file' => '/files/elements',
      'partner_image' => '/images/partners',
      'config_image' => '/images/config',
      'import_file' => '/imports',
      'general_file' => '/files',
      'default_file' => '/default',
    ];

    public function __construct(DocumentManagerFactory $dmFactory)
    {
        $this->dmFactory = $dmFactory;
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        return $this->getDirectoryPath($object);
    }

    public function getDirectoryPath($object)
    {
        $path = $this->BASE_PATH . $this->dmFactory->getCurrentDbName() . $this->PATHS[$object->getVichUploadFileKey()];
        $path .= $object->getCustomDirectory();
        return $path;
    }
}
