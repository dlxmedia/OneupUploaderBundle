<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Uploader\Response;

class EmptyResponse extends AbstractResponse
{
    public function assemble(): array
    {
        return $this->data;
    }
}
