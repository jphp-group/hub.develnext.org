<?php
namespace hub;

use php\io\Stream;
use php\lib\fs;
use php\lib\str;
use twig\TwigLoader;
use function var_dump;

class HubTwigStreamLoader extends TwigLoader
{
    private $prefix;
    private $suffix;
    private $charset;

    /**
     * Read template to Stream.
     *
     * @param string $name
     * @return Stream
     */
    function read(string $name): Stream
    {
        return Stream::of("{$this->prefix}{$name}{$this->suffix}");
    }

    /**
     * @param string $charset
     */
    function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * @param string $prefix
     */
    function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $suffix
     */
    function setSuffix(string $suffix): void
    {
        $this->suffix = $suffix;
    }

    /**
     * @param string $relativePath
     * @param string $anchorPath
     * @return string
     */
    function resolveRelativePath(string $relativePath, string $anchorPath): string
    {
        return $relativePath;
    }

    /**
     * @param string $templateName
     * @return string
     */
    function createCacheKey(string $templateName): string
    {
        return $templateName;
    }
}