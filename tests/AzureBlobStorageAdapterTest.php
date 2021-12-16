<?php
declare(strict_types=1);

namespace MedTrainer\Flysystem\AzureBlobStorage\Tests;

use Exception;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use MedTrainer\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesResult;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Blob\Models\PutBlobResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class AzureBlobStorageAdapterTest extends TestCase
{
    const FILE_TEST = 'test_image.png';

    const DIR_TEST = 'mt';

    /** @var MockObject|LoggerInterface  */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects(self::any())
            ->method('debug');
        $this->logger->expects(self::any())
            ->method('error');
        parent::setUp();
    }

    public function testCreation(): void
    {
        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $this->assertInstanceOf(AzureBlobStorageAdapter::class, $service, 'Error on creation of the class');
    }

    /**
     * @dataProvider getDataFileExists
     */
    public function testFileExists(
        string $fileName,
        bool $expectedResult
    ) {
        $blobClient = $this->createMock(BlobRestProxy::class);
        if ($expectedResult) {
            $blobClient->expects(self::any())
                ->method('getBlob')
                ->willReturn($expectedResult);
        } else {
            $blobClient->expects(self::any())
                ->method('getBlob')
                ->willThrowException(new Exception('File not found'));
        }

        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $result = $service->fileExists($fileName);
        $this->assertSame($expectedResult, $result);
    }

    public function getDataFileExists(): array
    {
        return [
            'fileExists' => [
                'fileName' => self::FILE_TEST,
                'expected' => true
            ],
            'fileDoesNtExists' => [
                'fileName' => self::FILE_TEST,
                'expected' => false
            ]

        ];
    }

    /**
     * @dataProvider getDataWrite
     */
    public function testWrite($destinationFile, $sourceFile, $config): void
    {
        $responseClient = $this->createMock(PutBlobResult::class);
        $responseClient->expects(self::any())
            ->method('getLastModified')
            ->willReturn(new \DateTimeImmutable());
        ;

        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('createBlockBlob')
            ->willReturn($responseClient);
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $statusWrite = true;

        try {
            $service->write($destinationFile, $sourceFile, new Config($config));
        } catch (Exception $exception) {
            $statusWrite = false;
        }

        $this->assertTrue($statusWrite);
    }

    /**
     * @dataProvider getDataWrite
     */
    public function testWriteStream($destinationFile, $sourceFile, $config)
    {
        $responseClient = $this->createMock(PutBlobResult::class);
        $responseClient->expects(self::any())
            ->method('getLastModified')
            ->willReturn(new \DateTimeImmutable());
        ;
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('createBlockBlob')
            ->willReturn($responseClient);
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default', '');
        $statusWrite = true;

        try {
            $service->writeStream($destinationFile, fopen($sourceFile, 'r'), new Config($config));
        } catch (Exception $exception) {
            $statusWrite = false;
        }

        $this->assertTrue($statusWrite, 'Failing the upload of a file');
    }

    public function getDataWrite(): array
    {
        return [
            'testWithOutMime' => [
                'destinationFile' => sprintf('mt/%s', self::FILE_TEST),
                'sourceFile' => sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST),
                'configuration' => []
            ],
            'testWithMime' => [
                'destinationFile' => self::FILE_TEST,
                'sourceFile' => sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST),
                'configuration' => [
                    'ContentType' => 'image/png'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getRead
     */
    public function testRead($path, $sourceFile, $expected)
    {
        $responseClient = $this->createMock(GetBlobResult::class);
        $responseClient->expects(self::any())
            ->method('getContentStream')
            ->willReturn(fopen($sourceFile, 'r'));
        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('getBlob')
            ->willReturn($responseClient);
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $response = $service->read($path);

        $this->assertSame( stream_get_contents($expected), $response, 'Failed to get the resourceFile');
    }

    /**
     * @dataProvider getRead
     */
    public function testReadStream($path, $sourceFile, $expected)
    {
        $responseClient = $this->createMock(GetBlobResult::class);
        $responseClient->expects(self::any())
            ->method('getContentStream')
            ->willReturn(fopen($sourceFile, 'r'));
        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('getBlob')
            ->willReturn($responseClient);
        //$blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $response = $service->readStream($path);

        $this->assertTrue((is_resource($response)), 'Failing to get the resource');
    }

    public function getRead(): array
    {
        return [
            'fileExist' => [
                'path' => sprintf('mt/%s', self::FILE_TEST),
                'sourceFile' => sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST),
                'expected' => fopen(sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST), 'r')
            ],
            'fileDoesNotExists' => [
                'path' => self::FILE_TEST,
                'sourceFile' => sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST),
                'expected' => fopen(sprintf('%s/%s/%s', __DIR__, 'files', self::FILE_TEST), 'r')
            ],
        ];
    }

    /**
     * @dataProvider getDelete
     */
    public function testDelete($exception)
    {
        $blobClient = $this->createMock(BlobRestProxy::class);
        if ($exception) {
            $responseInterface = $this->createMock(ResponseInterface::class);
            $exceptionClass = new ServiceException($responseInterface);
            $blobClient->expects(self::any())
                ->method('deleteBlob')
                ->willThrowException($exceptionClass);
        } else {
            $blobClient->expects(self::any())
                ->method('deleteBlob')
                ->willReturn(null);
        }

        if ($exception) {
            $this->expectException(Exception::class);
        }

        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $response = true;
        $service->delete(self::FILE_TEST);
        $this->assertTrue($response);
    }

    public function testDeleteDir()
    {
        $list = ListBlobsResult::create([
            'Blobs' => [
                [
                    'Name' => self::FILE_TEST
                ]
            ]
        ]);

        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('listBlobs')
            ->willReturn($list);
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default', 'adfasdf');
        $assertion = true;
        try {
            $service->deleteDirectory(self::DIR_TEST);
        } catch (Exception $exception) {
            $assertion = false;
        }

        $this->assertTrue($assertion, "Failing the delete of the folder");
    }

    public function getDelete(): array
    {
        return [
            'fileFound' => [
                'exception' => false
            ],
            'fileNotFound' => [
                'exception' => true
            ],
        ];
    }

    public function testCreateDirectory()
    {
        // Azure doesnt provide create a folder
        $this->expectException(UnableToCreateDirectory::class);
        $blobClient = $this->createMock(BlobRestProxy::class);
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $service->createDirectory(self::DIR_TEST, new Config());
    }

    public function testGetMime()
    {
        $blobClient = $this->createMock(BlobRestProxy::class);
        $blobClient->expects(self::any())
            ->method('getBlobProperties')
            ->willReturn(GetBlobPropertiesResult::create([
                  'server' => "Azurite-Blob/3.14.3",
                  'last-modified' => "Thu, 16 Dec 2021 17:42:12 GMT",
                  'x-ms-creation-time' => "Thu, 16 Dec 2021 17:42:12 GMT",
                  'x-ms-blob-type' => "BlockBlob",
                  'x-ms-lease-state' => "available",
                  'x-ms-lease-status' => "unlocked",
                  'content-length' => "4445",
                  'content-type' => "image/png",
                  'etag' => "0x1B6041BF1747F80",
                  'content-md5' => "YDE4L4f/zE+XvOicUOGk0g==",
                  'x-ms-client-request-id' => "61bb8af65161c",
                  'x-ms-request-id' => "aa5c1409-8868-4d76-8c7c-5e6254ead384",
                  'x-ms-version' => "2020-10-02",
                  'date' => "Thu, 16 Dec 2021 18:52:38 GMT",
                  'accept-ranges' => "bytes",
                  'x-ms-server-encrypted' => "true",
                  'x-ms-access-tier' => "Hot",
                  'x-ms-access-tier-inferred' => "true",
                  'x-ms-access-tier-change-time' => "Thu, 16 Dec 2021 17:42:12 GMT",
                  'connection' => "keep-alive",
                  'keep-alive' => "timeout=5",
                  'x-ms-continuation-location-mode' => "PrimaryOnly"
            ]));
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $response = $service->mimeType(self::FILE_TEST);
        $this->assertInstanceOf(FileAttributes::class, $response);
        $response = $service->lastModified(self::FILE_TEST);
        $this->assertInstanceOf(FileAttributes::class, $response);
        $response = $service->fileSize(self::FILE_TEST);
        $this->assertInstanceOf(FileAttributes::class, $response);
    }

    //    public function testListContents()
    //    {
    //        $responseClient = $this->createMock(GetBlobResult::class);
    //        $blobClient = $this->createMock(BlobRestProxy::class);
    //        $blobClient->expects(self::any())
    //            ->method('listBlobs')
    //            ->willReturn($responseClient);
    ////        $blobClient->expects(self::any())
    ////            ->method('getBlob')
    ////            ->willReturn(ListBlobsResult::create([
    ////                [
    ////                    "@attributes" => [
    ////                        "ServiceEndpoint" => "http://azurite:10000/devstoreaccount1",
    ////                        "ContainerName" => "default",
    ////                    ],
    ////                    "Prefix" => null,
    ////                    "Marker" => null,
    ////                    "MaxResults" => "5000",
    ////                    "Delimiter" => null,
    ////                    "Blobs" => [
    ////                        "Blob" => [
    ////                            [
    ////                                "Name" => "mt/test/index.png",
    ////                                "Properties" => [
    ////                                    "Creation-Time" => "Thu, 16 Dec 2021 18:46:37 GMT",
    ////                                    "Last-Modified" => "Thu, 16 Dec 2021 18:46:37 GMT",
    ////                                    "Etag" => "0x1BFFA769D7D9830",
    ////                                    "Content-Length" => "4445",
    ////                                    "Content-Type" => "image/png",
    ////                                    "Content-Encoding" => null,
    ////                                    "Content-Language" => null,
    ////                                    "Content-MD5" => "YDE4L4f/zE+XvOicUOGk0g==",
    ////                                    "Content-Disposition" => null,
    ////                                    "Cache-Control" => null,
    ////                                    "BlobType" => "BlockBlob",
    ////                                    "LeaseStatus" => "unlocked",
    ////                                    "LeaseState" => "available",
    ////                                    "ServerEncrypted" => "true",
    ////                                    "AccessTier" => "Hot",
    ////                                    "AccessTierInferred" => "true",
    ////                                    "AccessTierChangeTime" => "Thu, 16 Dec 2021 18:46:37 GMT",
    ////                                ],
    ////                            ],
    ////                            [
    ////                                "Name" => "mt/test_image.png",
    ////                                "Properties" => [
    ////                                    "Creation-Time" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                    "Last-Modified" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                    "Etag" => "0x2195366C7689E80",
    ////                                    "Content-Length" => "4445",
    ////                                    "Content-Type" => "image/png",
    ////                                    "Content-MD5" => "YDE4L4f/zE+XvOicUOGk0g==",
    ////                                    "BlobType" => "BlockBlob",
    ////                                    "LeaseStatus" => "unlocked",
    ////                                    "LeaseState" => "available",
    ////                                    "ServerEncrypted" => "true",
    ////                                    "AccessTier" => "Hot",
    ////                                    "AccessTierInferred" => "true",
    ////                                    "AccessTierChangeTime" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                ],
    ////                            ],
    ////                            [
    ////                                "Name" => "test_image.png",
    ////                                "Properties" => [
    ////                                    "Creation-Time" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                    "Last-Modified" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                    "Etag" => "0x1B6041BF1747F80",
    ////                                    "Content-Length" => "4445",
    ////                                    "Content-Type" => "image/png",
    ////                                    "Content-MD5" => "YDE4L4f/zE+XvOicUOGk0g==",
    ////                                    "BlobType" => "BlockBlob",
    ////                                    "LeaseStatus" => "unlocked",
    ////                                    "LeaseState" => "available",
    ////                                    "ServerEncrypted" => "true",
    ////                                    "AccessTier" => "Hot",
    ////                                    "AccessTierInferred" => "true",
    ////                                    "AccessTierChangeTime" => "Thu, 16 Dec 2021 17:42:12 GMT",
    ////                                ],
    ////                            ],
    ////                        ],
    ////                    ],
    ////                    "NextMarker" => null,
    ////                ]
    ////            ]));
    ////        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
    //        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
    //        $response = $service->listContents('', true);
    //        $this->assertIsIterable($response, 'Error getting the list of the content');
    //    }

    /**
     * @dataProvider getCopyFiles
     */
    public function testCopy($exception)
    {
        $blobClient = $this->createMock(BlobRestProxy::class);
        if ($exception) {
            $blobClient->expects(self::any())
                ->method('copyBlob')
                ->willThrowException(new UnableToCopyFile(""));
            $this->expectException(UnableToCopyFile::class);
        } else {
            $blobClient->expects(self::any())
                ->method('copyBlob')
                ->willReturn(null);
            $this->expectNotToPerformAssertions();
        }

        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $service->copy(self::FILE_TEST, 'test.png', new Config());
    }

    /**
     * @dataProvider getCopyFiles
     */
    public function testMove($exception)
    {
        $blobClient = $this->createMock(BlobRestProxy::class);
        if ($exception) {
            $blobClient->expects(self::any())
                ->method('copyBlob')
                ->willThrowException(new UnableToCopyFile(""));
            $this->expectException(UnableToCopyFile::class);
        } else {
            $blobClient->expects(self::any())
                ->method('copyBlob')
                ->willReturn(null);
            $this->expectNotToPerformAssertions();
        }

        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $service->move(self::FILE_TEST, 'test.png', new Config());
    }


    public function getCopyFiles(): array
    {
        return [
            'withException' => ['exception' => false],
            'withoutException' => ['exception' => true],

        ];
    }

    public function tearDown(): void
    {
        $this->logger = null;
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
}
