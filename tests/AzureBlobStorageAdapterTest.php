<?php

namespace MedTrainer\Flysystem\AzureBlobStorage\Tests;

use League\Flysystem\Config;
use MedTrainer\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Blob\Models\PutBlobResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AzureBlobStorageAdapterTest extends TestCase
{
    const FILE_TEST = 'test_image.png';

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
                ->willThrowException(new \Exception('File not found'));
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
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $statusWrite = true;

        try {
            $service->write($destinationFile, $sourceFile, new Config($config));
        } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
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
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $response = $service->readStream($path);

        $this->assertTrue((is_resource($response)), 'Failing to get the resource');
    }

    public function getRead(): array
    {
        return [
            'fileExists' => [
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


    public function tearDown(): void
    {
        $this->logger = null;
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
}
