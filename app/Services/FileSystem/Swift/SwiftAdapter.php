<?php namespace App\Services\FileSystem\Swift;
/**
 * Copyright 2020 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use League\Flysystem\{Config,
    DirectoryAttributes,
    FileAttributes,
    FilesystemAdapter,
    FilesystemException,
    UnableToCheckDirectoryExistence,
    UnableToCheckFileExistence,
    UnableToCopyFile,
    UnableToDeleteDirectory,
    UnableToDeleteFile,
    UnableToMoveFile,
    UnableToReadFile,
    UnableToRetrieveMetadata,
    UnableToSetVisibility,
    UnableToWriteFile,
    UrlGeneration\PublicUrlGenerator};
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use Throwable;

/**
 * Class SwiftAdapter
 * @package App\Services\FileSystem\Swift
 */
final class SwiftAdapter implements FilesystemAdapter, PublicUrlGenerator
{
    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @param int $size
     * @return FileAttributes
     */
    private function upload(string $path, string $contents, Config $config, int $size = 0): FileAttributes
    {

        $data = $this->getWriteData($path);
        $type = 'content';

        if (is_a($contents, 'GuzzleHttp\Psr7\Stream')) {
            $type = 'stream';
        }

        $data[$type] = $contents;

        try {
            // Create large object if the stream is larger than 300 MiB (default).
            if ($type === 'stream' && $size > $config->get('swiftLargeObjectThreshold', 314572800)) {
                // Set the segment size to 100 MiB by default as suggested in OVH docs.
                $data['segmentSize'] = $config->get('swiftSegmentSize', 104857600);
                // Set segment container to the same container by default.
                $data['segmentContainer'] = $config->get('swiftSegmentContainer', $this->container->name);

                $response = $this->container->createLargeObject($data);
            } else {
                $response = $this->container->createObject($data);
            }

            return $this->normalizeObject($response);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }


    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, new Stream($contents), $config, fstat($contents)['size']);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {
        $object = $this->getObjectInstance($path);
        try {
            $object->delete();
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, '', $e);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        $object = $this->getObject($path);
        try {
            $stream = $object->download();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            return $stream->getContents();
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $path)
    {
        $object = $this->getObject($path);
        try {
            $stream = $object->download([
                'requestOptions' => [
                    'stream' => true,
                ],
            ]);
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            return StreamWrapper::getResource($stream);
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @param string $path
     * @param bool $deep
     * @return iterable
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $path = trim($path, '/');
        $prefix = empty($path) ? '' : $path . '/';
        /** @var iterable<StorageObject> $objects */
        $objects = $this->container->listObjects(['prefix' => $prefix]);

        if ($deep) {
            foreach ($objects as $object) {
                yield $this->normalizeObject($object);
            }
        } else {
            $lastYieldedDirectory = null;

            foreach ($objects as $object) {
                $dirname = dirname($object->name);
                if ('.' === $dirname) {
                    // A dot is returned if there is no slash in path
                    $dirname = '';
                }

                if ($dirname === $path) {
                    yield $this->normalizeObject($object);
                } elseif (str_starts_with($object->name, empty($path) ? '' : $path . '/')) {
                    $relativeName = trim(substr($object->name, strlen($path)), '/');
                    $firstDirectory = explode('/', $relativeName)[0];

                    if ($lastYieldedDirectory !== $firstDirectory) {
                        $lastYieldedDirectory = $firstDirectory;

                        yield new DirectoryAttributes(trim(sprintf('%s/%s', $path, $firstDirectory), '/'));
                    }
                }
            }
        }

    }


    /**
     * @param string $path
     * @return string[]
     */
    protected function getWriteData(string $path): array
    {
        return ['name' => $path];
    }

    /**
     * Get an object instance.
     *
     * @param string $path
     *
     * @return StorageObject
     */
    protected function getObjectInstance(string $path): StorageObject
    {
        return $this->container->getObject($path);
    }

    /**
     * Get an object instance and retrieve its metadata from storage.
     *
     * @param string $path
     *
     * @return StorageObject
     */
    protected function getObject(string $path): StorageObject
    {
        $object = $this->getObjectInstance($path);
        $object->retrieve();
        return $object;
    }


    public function getTemporaryLink(string $path): ?string
    {
        return $this->getUrl($path);
    }

    public function getTemporaryUrl(string $path): ?string
    {
        return $this->getUrl($path);
    }

    public function getUrl(string $path): ?string
    {
        $obj = $this->container->getObject($path);
        return $obj->getPublicUri();
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->container->objectExists($path);
        } catch (Throwable $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->container
                ->listObjects(['prefix' => $path])
                ->valid();
        } catch (\Exception $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        // Make sure a slash is added to the end.
        $path = rtrim($path, '/') . '/';

        $objects = $this->container->listObjects([
            'prefix' => $path
        ]);

        try {
            foreach ($objects as $object) {
                try {
                    $object->delete();
                } catch (BadResponseError $e) {
                    if (404 !== $e->getResponse()->getStatusCode()) {
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {

    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'OpenStack Swift does not support per-file visibility.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'OpenStack Swift does not support per-file visibility.');
    }

    public function mimeType(string $path): FileAttributes
    {
        $object = $this->container->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->normalizeObject($object);

        if (null === $fileAttributes->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path, 'The mime-type is empty.');
        }

        return $fileAttributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        $object = $this->container->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->normalizeObject($object);

        if (null === $fileAttributes->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path, 'The mime-type is empty.');
        }

        return $fileAttributes;
    }

    public function fileSize(string $path): FileAttributes
    {
        $object = $this->container->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->normalizeObject($object);

        if (null === $fileAttributes->fileSize()) {
            throw UnableToRetrieveMetadata::fileSize($path, sprintf('Invalid file size "%s".', $object->contentLength));
        }

        return $fileAttributes;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $object = $this->container->getObject($source);

        try {
            $object->copy([
                'destination' => sprintf('%s/%s', $this->container->name, $destination),
            ]);
        } catch (\Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function publicUrl(string $path, Config $config): string
    {
        return $this->getUrl($path);
    }


    /**
     * @param StorageObject $object
     * @return FileAttributes
     */
    private function normalizeObject(StorageObject $object): FileAttributes
    {
        $fileSize = (int)$object->contentLength;

        if (0 === $fileSize && '0' !== $object->contentLength) {
            $fileSize = null;
        }

        /** @var \DateTimeInterface|string $lastModified */
        $lastModified = $object->lastModified;

        if ($lastModified instanceof \DateTimeInterface) {
            $lastModified = $lastModified->getTimestamp();
        } else {
            $lastModified = strtotime($lastModified);
            if (!($lastModified > 0)) {
                $lastModified = null;
            }
        }

        $mimeType = empty($object->contentType) ? null : $object->contentType;

        return new FileAttributes(
            $object->name,
            $fileSize,
            null,
            $lastModified,
            $mimeType
        );
    }
}

