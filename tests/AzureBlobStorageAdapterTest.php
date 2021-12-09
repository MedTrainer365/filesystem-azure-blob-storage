<?php

namespace MedTrainer\Flysystem\AzureBlobStorage\Tests;

use MedTrainer\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AzureBlobStorageAdapterTest extends TestCase
{

    public function testCreation(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
        $service = new AzureBlobStorageAdapter($blobClient, $logger, 'default');
        $this->assertInstanceOf(AzureBlobStorageAdapter::class, $service, 'Error on creation of the class');
    }

    /**
     * @dataProvider getDataFileExists
     */
    public function testFileExists(
        string $fileName,
        bool $expectedResult
    )
    {
        $logger = $this->createMock(LoggerInterface::class);
//        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
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

        $service = new AzureBlobStorageAdapter($blobClient, $logger, 'default');
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
    public function testWrite()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $blobClient = BlobRestProxy::createBlobService('AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;DefaultEndpointsProtocol=http;BlobEndpoint=http://azurite:10000/devstoreaccount1;QueueEndpoint=http://azurite:10001/devstoreaccount1;TableEndpoint=http://azurite:10002/devstoreaccount1;');
//        $blobClient = $this->createMock(BlobRestProxy::class);
        $service = new AzureBlobStorageAdapter($blobClient, $logger, 'default');
        $service->write(__DIR__.'/files/test_image.png');
//        $result = $service->fileExists($fileName);
//        $this->assertSame($expectedResult, $result);

    }
}
