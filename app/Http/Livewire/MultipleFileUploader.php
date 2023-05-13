<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MultipleFileUploader extends Component
{
    use WithFileUploads;

    // @var array Temporary file uploads
    public $uploads = [];

    // @var array Master files
    public $files = [];

    // @var int Upload file chunk size
    public $chunkSize = 1024 * 1000 * 0.1;

    // @var int Upload files max count
    public $uploadsMaxCount = 0;

    // @var int Upload files count
    public $uploadsCount = 0;

    // @var int Master files count
    public $filesCount = 0;

    // @array Map of file uploads to their temporary index
    public $fileUploadIdx = [];

    // @var int Upload max limit
    public $uploadMaxLimit = 10;

    // protected $rules = [
    //     'uploads.*' => 'image|max:10240',
    // ];

    public function mount()
    {
        // Initialize Temporary file uploads
        $this->uploads = [];

        // Master files XXX Get saved data from DB
        $this->files = [];
    }

    /**
     * Set file details
     *
     * @param [type] $index
     * @param [type] $fileName
     * @param [type] $fileSize
     * @return void
     */
    public function setFileDetails($index, $fileName, $fileSize)
    {
        // Make a map of file uploads to their temporary index
        $this->fileUploadIdx[$index] = $this->filesCount;
        $fileIndex = $this->fileUploadIdx[$index];

        // Set file details
        $file = [
            'id' => $fileIndex,
            'fileName' => md5(uniqid(rand(), true)),
            'originalFileName' => $fileName,
            'fileSize' => $fileSize,
            'fileChunk' => null,
            'progress' => 0,
            'fileRef' => null,
        ];

        // array_unshift($this->files, $file);
        array_push($this->files, $file);

        // Master file count increment
        $this->filesCount++;

        // Temporary file count increment
        $this->uploadsMaxCount++;
    }

    public function setUpdateFileDetails($index, $fileName, $fileSize, $fileId)
    {
        // Make a map of file uploads to their temporary index
        $this->fileUploadIdx[$index] = $fileId;
        $fileIndex = array_search($fileId, array_column($this->files, 'id'));

        // Set file details
        $this->files[$fileIndex] = [
            'id' => $fileId,
            'fileName' => md5(uniqid(rand(), true)),
            'originalFileName' => $fileName,
            'fileSize' => $fileSize,
            'fileChunk' => null,
            'progress' => 0,
            'fileRef' => null,
        ];
    }

    /**
     * Can upload more files
     *
     * @param integer $count
     * @return boolean
     */
    public function resetUploadFiles(int $count)
    {
        $this->uploads = [];
        $this->uploadsCount = 0;
        $this->resetValidation();
        if (count($this->files) + $count > $this->uploadMaxLimit) {
            $this->addError('uploads.*', "最大{$this->uploadMaxLimit}個までアップロードできます。");
            return false;
        }
        return true;
    }

    /**
     * Delete file from files array
     *
     * @param integer $value
     * @return void
     */
    public function deleteFile(int $fileId)
    {
        $fileIndex = array_search($fileId, array_column($this->files, 'id'));
        unset($this->files[$fileIndex]);
        $this->files = array_values($this->files);
    }

    /**
     * Reorder files
     *
     * @param array $orderedIds
     * @return void
     */
    public function reorderFiles($orderedIds)
    {
        $this->files = collect($orderedIds)->map(function ($id) {
            return collect($this->files)->where('id', (int) $id)->first();
        })->toArray();
    }

    /**
     * Upload file chunk
     *
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updatedUploads($value, $key)
    {
        Log::debug($key);

        list($index, $attribute) = explode('.', $key);
        $index = intval($index);
        $fileId = $this->fileUploadIdx[$index];
        $livewireTmp = '/livewire-tmp/';

        //  Upload file chunk
        if ($attribute == 'fileChunk') {
            // Get file detail from id
            $fileIndex = array_search($fileId, array_column($this->files, 'id'));
            $file = $this->files[$fileIndex];

            // File name for file merge
            $fileName = $file['fileName'];
            $finalPath = Storage::path($livewireTmp . $fileName);

            // Chunk file
            $chunkName = $this->uploads[$index]['fileChunk']->getFileName();
            $chunkPath = Storage::path($livewireTmp . $chunkName);

            // Check if chunk exists
            if (!file_exists($chunkPath)) {
                return;
            }
            $chunk = fopen($chunkPath, 'rb');
            $buff = fread($chunk, $this->chunkSize);
            fclose($chunk);

            // Merge Together
            $final = fopen($finalPath, 'ab');
            fwrite($final, $buff);
            fclose($final);
            unlink($chunkPath);

            // Progress
            $curSize = Storage::size($livewireTmp . $fileName);
            $this->files[$fileIndex]['progress'] = $curSize / $file['fileSize'] * 100;

            // Upload Complete
            if ($this->files[$fileIndex]['progress'] >= 100) {
                $this->files[$fileIndex]['fileRef'] = TemporaryUploadedFile::createFromLivewire('/' . $fileName);

                // Temporary file count increment
                $this->uploadsCount++;
                Log::debug($this->uploadsMaxCount . ' / ' . $this->uploadsCount);

                // Reset if max count reached
                // if ($this->uploadsMaxCount <= $this->uploadsCount) {
                //     Log::debug('reset');
                //     $this->uploads = [];
                //     $this->uploadsCount = 0;
                // }
            }
        }
    }

    public function render()
    {
        return view('livewire.multiple-file-uploader');
    }
}
