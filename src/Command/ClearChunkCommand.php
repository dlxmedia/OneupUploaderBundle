<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Command;

use Oneup\UploaderBundle\Uploader\Chunk\ChunkManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('oneup:uploader:clear-chunks')]
class ClearChunkCommand extends Command
{
    private ChunkManager $manager;

    public function __construct(ChunkManager $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clear chunks according to the max-age you defined in your configuration.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manager->clear();

        return Command::SUCCESS;
    }
}
