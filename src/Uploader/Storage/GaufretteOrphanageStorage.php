<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Uploader\Storage;

use Gaufrette\File;
use Oneup\UploaderBundle\Uploader\Chunk\Storage\GaufretteStorage as GaufretteChunkStorage;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\GaufretteFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GaufretteOrphanageStorage extends GaufretteStorage implements OrphanageStorageInterface
{
    protected StorageInterface $storage;

    protected SessionInterface $session;

    protected GaufretteChunkStorage $chunkStorage;

    protected array $config;

    protected string $type;

    public function __construct(StorageInterface $storage, RequestStack $requestStack, GaufretteChunkStorage $chunkStorage, array $config, string $type)
    {
        /*
         * initiate the storage on the chunk storage's filesystem
         * the stream wrapper is useful for metadata.
         */
        parent::__construct($chunkStorage->getFilesystem(), $chunkStorage->buffersize, $chunkStorage->getStreamWrapperPrefix());

        /** @var Request $request */
        $request = $requestStack->getCurrentRequest();

        $this->storage = $storage;
        $this->chunkStorage = $chunkStorage;
        $this->session = $request->getSession();
        $this->config = $config;
        $this->type = $type;
    }

    public function upload(FileInterface|\Symfony\Component\HttpFoundation\File\File $file, string $name, string $path = null): FileInterface|\Symfony\Component\HttpFoundation\File\File
    {
        if (!$this->session->isStarted()) {
            throw new \RuntimeException('You need a running session in order to run the Orphanage.');
        }

        return parent::upload($file, $name, $this->getPath());
    }

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
                } catch (\Exception $e) {
                    // well, we tried.
                    continue;
                }
            }

            return $return;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getFiles(): array
    {
        $keys = $this->chunkStorage->getFilesystem()->listKeys($this->getPath());
        $keys = $keys['keys'];
        $files = [];

        foreach ($keys as $key) {
            // gotta pass the filesystem to both as you can't get it out from one..
            $files[$key] = new GaufretteFile(new File($key, $this->chunkStorage->getFilesystem()), $this->chunkStorage->getFilesystem());
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
