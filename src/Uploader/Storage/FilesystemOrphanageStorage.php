<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Uploader\Storage;

use Oneup\UploaderBundle\Uploader\Chunk\Storage\FilesystemStorage as ChunkStorage;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FilesystemOrphanageStorage extends FilesystemStorage implements OrphanageStorageInterface
{
    protected StorageInterface $storage;

    protected SessionInterface $session;

    protected ChunkStorage $chunkStorage;

    protected array $config;

    protected string $type;

    public function __construct(StorageInterface $storage, RequestStack $requestStack, ChunkStorage $chunkStorage, array $config, string $type)
    {
        parent::__construct($config['directory']);

        /** @var Request $request */
        $request = $requestStack->getCurrentRequest();

        $this->storage = $storage;
        $this->session = $request->getSession();
        $this->chunkStorage = $chunkStorage;
        $this->config = $config;
        $this->type = $type;
    }

    public function upload(FileInterface|File $file, string $name, string $path = null): FileInterface|File
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

            foreach ($files as $file) {
                $return[] = $this->storage->upload(
                    new FilesystemFile(new File($file->getPathname())),
                    ltrim(str_replace($this->getFindPath(), '', (string) $file), '/'));
            }

            return $return;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getFiles(): Finder
    {
        $finder = new Finder();

        try {
            $finder->in($this->getFindPath())->files();
        } catch (\InvalidArgumentException $e) {
            //catch non-existing directory exception.
            //This can happen if getFiles is called and no file has yet been uploaded

            //push empty array into the finder so we can emulate no files found
            $finder->append([]);
        }

        return $finder;
    }

    protected function getPath(): string
    {
        return sprintf('%s/%s', $this->session->getId(), $this->type);
    }

    protected function getFindPath(): string
    {
        return sprintf('%s/%s', $this->config['directory'], $this->getPath());
    }
}
