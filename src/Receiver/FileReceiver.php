<?php
namespace Roae\Laravel\ChunkUpload\Receiver;

use Illuminate\Http\Request;
use Roae\Laravel\ChunkUpload\Config\AbstractConfig;
use Roae\Laravel\ChunkUpload\Handler\AbstractHandler;
use Roae\Laravel\ChunkUpload\Save\AbstractSave;
use Roae\Laravel\ChunkUpload\Save\ChunkSave;
use Roae\Laravel\ChunkUpload\Save\SingleSave;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Roae\Laravel\ChunkUpload\Storage\ChunkStorage;

class FileReceiver
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UploadedFile|null
     */
    protected $file;

    /**
     * The handler that detects what upload proccess is beeing used
     *
     * @var AbstractHandler
     */
    protected $handler = null;

    /**
     * The chunk storage
     *
     * @var ChunkStorage
     */
    protected $chunkStorage;

    /**
     * The current config
     * @var AbstractConfig
     */
    protected $config;

    /**
     * The file receiver for the given file index
     *
     * @param string              $fileIndex the desired file index in requests files
     * @param Request             $request the current request
     * @param string              $handlerClass the handler class name for detecting the file upload
     * @param ChunkStorage|null   $chunkStorage the chunk storage, on null will use the instance from app container
     * @param AbstractConfig|null $config the config, on null will use the instance from app container
     */
    public function __construct($fileIndex, Request $request, $handlerClass, $chunkStorage = null, $config = null)
    {
        $this->request = $request;
        $this->file = $request->file($fileIndex);
        $this->chunkStorage = is_null($chunkStorage) ? ChunkStorage::storage() : $chunkStorage;
        $this->config = is_null($config) ? AbstractConfig::config() : $config;

        if ($this->isUploaded()) {
            $this->handler = new $handlerClass($this->request, $this->file, $this->config);
        }
    }

    /**
     * Checks if the file was uploaded
     *
     * @return bool
     */
    public function isUploaded()
    {
        return is_object($this->file);
    }

    /**
     * Tries to handle the upload request. If the file is not uploaded, returns false. If the file
     * is present in the request, it will create the save object.
     *
     * If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
     * which doesnt nothing at this moment.
     *
     * @return bool|AbstractSave
     */
    public function receive()
    {
        if (!$this->isUploaded()) {
            return false;
        }

        if ($this->handler->isChunkedUpload()) {
            return new ChunkSave($this->file, $this->handler, $this->chunkStorage, $this->config);
        } else {
            return new SingleSave($this->file, $this->handler, $this->config);
        }
    }
}