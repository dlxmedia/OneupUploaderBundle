<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Uploader\Storage;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Oneup\UploaderBundle\Uploader\Chunk\Storage\FlysystemStorage as ChunkStorage;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FlysystemFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FlysystemOrphanageStorage extends FlysystemStorage implements OrphanageStorageInterface
{
    protected StorageInterface $storage;

    protected SessionInterface $session;

    protected ChunkStorage $chunkStorage;

    protected array $config;

    protected string $type;

    public function __construct(StorageInterface $storage, RequestStack $requestStack, ChunkStorage $chunkStorage, array $config, string $type)
    {
        /*
         * initiate the storage on the chunk storage's filesystem
         * the stream wrapper is useful for metadata.
         */
        parent::__construct($chunkStorage->getFilesystem(), $chunkStorage->bufferSize, $chunkStorage->getStreamWrapperPrefix());

        /** @var Request $request */
        $request = $requestStack->getCurrentRequest();

        $this->storage = $storage;
        $this->chunkStorage = $chunkStorage;
        $this->session = $request->getSession();
        $this->config = $config;
        $this->type = $type;
    }

    /**
     * @throws FilesystemException
     */
    public function upload(FileInterface|File $file, string $name, string $path = null): FileInterface|File
    {
        if (!$this->session->isStarted()) {
            throw new \RuntimeException('You need a running session in order to run the Orphanage.');
        }

        return parent::upload($file, $name, $this->getPath());
    }

    /**
     * @throws FilesystemException
     */
    public function uploadFiles(array $files = null): array
    {
        try {
            if (null === $files) {
                $files = $this->getFiles();
            }
            $return = [];

            foreach ($files as $key => $file) {
                try {
                    $return[] = $this->storage->upload($file, str_replace($this->getPath(), '', $key));
                } catch (\Exception) {
                    // well, we tried.
                    continue;
                }
            }

            return $return;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * @throws FilesystemException
     */
    public function getFiles(): array
    {
        $fileList = $this->chunkStorage
            ->getFilesystem()
            ->listContents($this->getPath());
        $files = [];

        /** @var StorageAttributes $fileDetail */
        foreach ($fileList as $fileDetail) {
            $key = $fileDetail->path();
            if ($fileDetail->isFile()) {
                $files[$key] = new FlysystemFile($key, $this->chunkStorage->getFilesystem());
            }
        }

        return $files;
    }

    protected function getPath(): string
    {
        // the storage is initiated in the root of the filesystem, from where the orphanage directory
        // should be relative.
        return sprintf('%s/%s/%s', $this->config['directory'], $this->session->getId(), $this->type);
    }
}
