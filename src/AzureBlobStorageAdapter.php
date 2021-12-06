<?php

namespace MedTrainer\Flysystem\AzureBlobStorage;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use  League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

final class AzureBlobStorageAdapter implements FilesystemAdapter
{
    /** @var BlobRestProxy */
    private  $client;

    /** @var string */
    private $container;

    private $maxResultsForContentsListing = 5000;

    public function __construct(BlobRestProxy $client, string $container)
    {
        $this->client = $client;
        $this->container = $container;
    }

    public function fileExists(string $path): bool
    {
        // TODO: Implement fileExists() method.
    }

    public function write(string $path, string $contents, Config $config): void
    {
        // TODO: Implement write() method.
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        // TODO: Implement writeStream() method.
    }

    public function read(string $path): string
    {
        // TODO: Implement read() method.
    }

    public function readStream(string $path)
    {
        // TODO: Implement readStream() method.
    }

    public function delete(string $path): void
    {
        // TODO: Implement delete() method.
    }

    public function deleteDirectory(string $path): void
    {
        // TODO: Implement deleteDirectory() method.
    }

    public function createDirectory(string $path, Config $config): void
    {
        // TODO: Implement createDirectory() method.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // TODO: Implement setVisibility() method.
    }

    public function visibility(string $path): FileAttributes
    {
        // TODO: Implement visibility() method.
    }

    public function mimeType(string $path): FileAttributes
    {
        // TODO: Implement mimeType() method.
    }

    public function lastModified(string $path): FileAttributes
    {
        // TODO: Implement lastModified() method.
    }

    public function fileSize(string $path): FileAttributes
    {
        // TODO: Implement fileSize() method.
    }

    public function listContents(string $path, bool $deep): iterable
    {
        // TODO: Implement listContents() method.
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // TODO: Implement move() method.
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        // TODO: Implement copy() method.
    }
}