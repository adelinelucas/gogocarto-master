<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @MongoDB\Document
 * @Vich\Uploadable
 */
class File extends AbstractFile
{
    /**
     * @var int
     *
     * @MongoDB\Id(strategy="INCREMENT")
     */
    private $id;

    /** @MongoDB\Field(type="string") */
    private $customDirectory = '';

    protected $vichUploadFileKey = 'general_file';

    /**
     * Get the value of customDirectory
     */ 
    public function getCustomDirectory()
    {
        return $this->customDirectory;
    }

    public function setFileName($fileName)
    {
        // Prevent resetting fileName to null, so we can edit the file without changing fileName
        if ($fileName) $this->fileName = $fileName;
        return $this;
    }

    /**
     * Set the value of customDirectory
     *
     * @return  self
     */ 
    public function setCustomDirectory($customDirectory)
    {
        $this->customDirectory = $customDirectory;

        return $this;
    }

    /**
     * Get the value of id
     *
     * @return  int
     */ 
    public function getId()
    {
        return $this->id;
    }
}
