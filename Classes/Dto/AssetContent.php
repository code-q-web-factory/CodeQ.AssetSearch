<?php

namespace CodeQ\AssetSearch\Dto;

use DateTime;

class AssetContent
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var int
     */
    protected $contentLength;

    /**
     * @var string
     */
    protected $language;

    /**
     * AssetContent constructor.
     * @param string $content
     * @param string $title
     * @param string $name
     * @param string $author
     * @param string $keywords
     * @param string $date
     * @param string $contentType
     * @param int $contentLength
     * @param string $language
     */
    public function __construct(string $content, string $title, string $name, string $author, string $keywords, string $date, string $contentType, int $contentLength, string $language)
    {
        $this->content = trim(preg_replace('/(\s|\\\\[rntv]{1})/', ' ', $content));
        $this->title = $title;
        $this->name = $name;
        $this->author = $author;
        $this->keywords = $keywords;
        $this->date = $date;
        $this->contentType = $contentType;
        $this->contentLength = $contentLength;
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date !== '' ? new DateTime($this->date) : null;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getContentLength(): int
    {
        return $this->contentLength;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return array
     */
    public function __toArray(): array
    {
        return [
            'content' => $this->content,
            'title' => $this->title,
            'name' => $this->name,
            'author' => $this->author,
            'keywords' => $this->keywords,
            'date' => $this->date,
            'contentType' => $this->contentType,
            'contentLength' => $this->contentLength,
            'language' => $this->language
        ];
    }
}
