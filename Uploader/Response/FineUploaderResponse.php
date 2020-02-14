<?php

declare(strict_types=1);

namespace Oneup\UploaderBundle\Uploader\Response;

class FineUploaderResponse extends AbstractResponse
{
    protected $success;
    protected $error;

    public function __construct()
    {
        $this->success = true;
        $this->error = null;

        parent::__construct();
    }

    public function assemble()
    {
        // explicitly overwrite success and error key
        // as these keys are used internaly by the
        // frontend uploader
        $data = $this->data;
        $data['success'] = $this->success;

        if ($this->success) {
            unset($data['error']);
        }

        if (!$this->success) {
            $data['error'] = $this->error;
        }

        return $data;
    }

    public function setSuccess($success)
    {
        $this->success = (bool) $success;

        return $this;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function setError($msg)
    {
        $this->error = $msg;

        return $this;
    }

    public function getError()
    {
        return $this->error;
    }
}
