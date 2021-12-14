<?php

namespace MedTrainer\Flysystem\AzureBlobStorage\Tests;

use League\Flysystem\Config;
use MedTrainer\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\PutBlobResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AzureBlobStorageAdapterTest extends TestCase
{
    /** @var MockObject|LoggerInterface  */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
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
                'fileName' => 'test_image.png',
                'expected' => true
            ],
            'fileDoesNtExists' => [
                'fileName' => 'test_image.png',
                'expected' => false
            ]

        ];
    }

    /**
     * @dataProvider getDataWrite
     */
    public function testWrite($destinationFile, $sourceFile, $config): void
    {
        //        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
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
        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $this->logger, 'default');
        $statusWrite = true;

        try {
            $service->writeStream($destinationFile, $sourceFile, new Config($config));
        } catch (\Exception $exception) {
            $statusWrite = false;
        }

        $this->assertTrue($statusWrite);
    }

    public function getDataWrite(): array
    {
        return [
            'testWithOutMime' => [
                'destinationFile' => 'file_demo.png',
                'sourceFile' => sprintf('%s/%s', __DIR__, 'files/test_image.png'),
                'configuration' => []
            ],
            'testWithMime' => [
                'destinationFile' => 'file_demo.png',
                'sourceFile' => sprintf('%s/%s', __DIR__, 'files/test_image.png'),
                'configuration' => [
                    'ContentType' => 'image/png'
                ]
            ]
        ];
    }




    public function tearDown(): void
    {
        $this->logger = null;
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
}
