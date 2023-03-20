<?php
namespace accounting;

use Throwable;

class ThrowableJson{
    private Throwable $exception;

    public function __construct(Throwable $exception ) {
        $this->exception = $exception;
    }

    public function getArray(): array {
        return ['type'=>get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTrace()
        ];
    }

    public function getJson(){
        return json_encode(['type'=>get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTrace()
        ],JSON_UNESCAPED_UNICODE);
    }
    public function getErrorResponse($code): string{
        http_response_code($code);
        return $this->exception->getMessage();//['type'=>get_class($this->exception),'responseText' => $this->exception->getMessage(),'code' => $this->exception->getCode()];
    }

    public function httpCode($code): ThrowableJson{
        http_response_code($code);
        return $this;
    }
}
