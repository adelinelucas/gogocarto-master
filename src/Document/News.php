<?php

declare(strict_types=1);

namespace App\Document;

use App\Enum\NewsStatus;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(repositoryClass="App\Repository\NewsRepository")
 */
class News
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ODM\Field
     */
    private $title;

    /**
     * @var string|null
     *
     * @ODM\Field
     */
    private $content;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    private $publicationDate;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     *
     * @see NewsStatus
     */
    private $status = NewsStatus::DRAFT;

    public function __construct()
    {
        $this->publicationDate = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): News
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setContent(string $content): News
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setPublicationDate(\DateTime $publicationDate): News
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getPublicationDate(): ?\DateTime
    {
        return $this->publicationDate;
    }

    public function setStatus(int $status): News
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function __toString(): string
    {
        return sprintf('Nouvelle %s (%d)', $this->title, $this->id);
    }
}
