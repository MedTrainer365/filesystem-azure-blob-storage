<?php

namespace MedTrainer\Flysystem\AzureBlobStorage;

use Exception;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobBlockOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Psr\Log\LoggerInterface;


final class AzureBlobStorageAdapter extends Adapter implements FilesystemAdapter
{
    protected static $metaOptions = [
        'CacheControl',
        'ContentType',
        'Metadata',
        'ContentLanguage',
        'ContentEncoding',
    ];

    /** @var BlobRestProxy */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $container;

    /** @var PathPrefixer */
    private $prefixer;

    /** @var bool|mixed  */
    private $publicAccess = false;

    /** @var array */
    private $metaData = [];

    /** @var MimeTypeDetector */
    private $mimeTypeDetector;

    /** @var bool */
    private $booted = false;

    private $maxResultsForContentsListing = 5000;

    /** @var array */
    protected $options = [];

    public function __construct(
        BlobRestProxy $client,
        LoggerInterface $logger,
        $container = 'default',
        $prefix = '',
        $publicAccess = false,
        array $metaData = [],
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->container = $container;
        $this->publicAccess = $publicAccess;
        $this->metaData = $metaData;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ? : new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        $response = true;
        try {
            $destination = $this->prefixer->prefixPath($path);
            $this->logger->debug(sprintf('file exists from: %s', $destination));
            $this->client->getBlob('default', $destination);
        } catch (Exception $exception) {
            $response = false;
            $this->logger->error($exception->getMessage());
        }

        return $response;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    protected function upload($path, $contents, Config $config): array
    {
        if (!$this->booted) {
            $this->initialize();
        }

        $destination = $this->prefixer->prefixPath($path);
        $this->logger->debug(sprintf('Uploading to: %s', $destination));

        $options = $this->createOptionsFromConfig($config);
        $stream = $contents;

        if (empty($options->getContentType())) {
            if (!is_resource($stream)) {
                $options->setContentType($this->mimeTypeDetector->detectMimeTypeFromFile($contents));
            } else {
                $metaData = stream_get_meta_data($stream);
                $options->setContentType($this->mimeTypeDetector->detectMimeTypeFromFile($metaData['uri']));
            }
        }

        if (!is_resource($stream)) {
            $stream = Utils::tryFopen($contents, "r");
        }

        $response = $this->client->createBlockBlob(
            $this->container,
            $destination,
            $stream,
            $options
        );

        return [
            'path' => $destination,
            'timestamp' => $response->getLastModified()->getTimestamp(),
            'type' => 'file',
        ];
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $response = $this->readStream($path);
        if (!isset($response['stream']) || ! is_resource($response['stream'])) {
            return $response;
        }

        $response['contents'] = stream_get_contents($response['stream']);
        unset($response['stream']);

        return $response;
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

    private function createOptionsFromConfig(Config $config): CreateBlockBlobOptions
    {
        $options = $config->get('blobOptions', new CreateBlockBlobOptions());
        foreach (static::$metaOptions as $option) {
            if (!$config->get($option)) {
                continue;
            }

            call_user_func([$options, "set$option"], $config->get($option));
        }

        if ($mimetype = $config->get('mimetype')) {
            $options->setContentType($mimetype);
        }

        return $options;
    }

    private function initialize():void
    {
        if (!$this->booted) {
            $this->createContainer($this->container, $this->publicAccess, $this->metaData);
        }
    }

    private function createContainer(string $container, bool $publicAccess = false, array $metaData = []):void
    {
        // OPTIONAL: Set public access policy and metadata.
        // Create container options object.
        $createContainerOptions = new CreateContainerOptions();

        // Set public access policy. Possible values are
        // PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
        // CONTAINER_AND_BLOBS: full public read access for container and blob data.
        // BLOBS_ONLY: public read access for blobs. Container data not available.
        // If this value is not specified, container data is private to the account owner.
        if (true === $publicAccess) {
            $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
        }

        // Set container metadata
        if ($metaData instanceof \Traversable) {
            foreach ($metaData as $key => $value) {
                $createContainerOptions->addMetaData($key, $value);
            }
        }

        try {
            $this->client->createContainer($container, $createContainerOptions);
        } catch (ServiceException $e) {
            $this->logger->error($e->getErrorMessage());
        }
    }
}
