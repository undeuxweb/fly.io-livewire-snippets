<div>
    <p>{{ now() }}</p>
    
    <div>
        <input type="file" id="myFiles" multiple wire:loading.attr="disabled">
        <button type="button" id="myButton" wire:click="$refresh">Refresh</button>

        @error('uploads.*') <div class="error text-red-600">{{ $message }}</div> @enderror

        <p>{{ now() }}</p>
    </div>

    @forelse ($files as $i => $file)
        <div wire:key="{{ $i }}">
            <p>ID: {{ $i }} {{ floor($file['progress']) }}% {{ now() }}</p>
            <p>
                File: {{ $file['fileName'] }} ({{ number_format($file['fileSize'] / 1024, 2) . ' KB'; }})
            </p>
            @if (isset($file['fileRef']))
                <img src="{{ $file['fileRef']->temporaryUrl() }}" width="100" />
            @endif
            <button type="button" wire:click="deleteFile({{ $i }})">削除</button>
        </div>
    @empty
    @endforelse

    @push('scripts')
    <script type="module">
        // Select the file input
        const filesSelector = document.querySelector('#myFiles');

        // Upload file list
        let fileList;

        // Chunk starts
        let chnkStarts = [];

        // Completed file list
        let completedFileList = 0;

        /**
         * Change event handler
         * @param {Event} event
         */
        const change = event => {
            fileList = [...filesSelector.files];
            // console.log(fileList);
            console.log(fileList.length);

            // Check if we can upload more files
            @this.call('canUploadMoreFiles', fileList.length).then(success => {
                console.log(success);
                if (success) {
                    fileList.forEach((file, index) => {
                        // Set file details
                        @this.call('setFileDetails', index, file.name, file.size);

                        // Reset chunk start
                        chnkStarts[index] = 0;

                        // Start uploading
                        livewireUploadChunk(index, file);
                    });
                } else {
                    filesSelector.value = '';
                }
            });
        }

        // Add event listener
        filesSelector.addEventListener('change', change);

        /**
         * Uploads a chunk of a file
         * @param {number} index
         * @param {File} file
         */
        const livewireUploadChunk = (index, file) => {
            // End of chunk is start + chunkSize OR file size, whichever is greater
            const chunkEnd = Math.min(chnkStarts[index] + @js($chunkSize), file.size);

            // Slice the file into a chunk
            const chunk = file.slice(chnkStarts[index], chunkEnd);
            console.log('chunkStart: ' + index + ' / ' + chnkStarts[index] + ' / ' + chunkEnd + ' file.size: ' + file.size);

            // Success callback is called when the file has been uploaded
            @this.upload('uploads.' + index + '.fileChunk', chunk, (uploadedFilename) => {
                console.log(uploadedFilename);

                // Error invalid file name
                if (uploadedFilename == 'livewire-tmp') {
                    alert('Error uploading file. Please try again.');

                    // Retry upload chunk file
                    livewireUploadChunk(index, file);
                }
            }, () => {
                // Error callback is called when the file could not be uploaded
                alert('Error uploading file. Please try again.');

                completedFileList ++;
            }, (e) => {
                // Progress callback is called multiple times while the file is being uploaded
                if (e.detail.progress >= 100) {
                    // Calculate next chunk start
                    chnkStarts[index] = Math.min(chnkStarts[index] + @js($chunkSize), file.size);

                    // Retry upload chunk file
                    if (chnkStarts[index] < file.size) {
                        // let _time = Math.floor((Math.random() * 2000) + 1);
                        console.log('retryChunk: ' + chnkStarts[index] + '<' + file.size);
                        setTimeout(livewireUploadChunk, 1000, index, file);
                    } else {
                        // Increment completed file list
                        completedFileList ++;
                        console.log('completedFileList: ' + fileList.length + ' / ' + completedFileList);

                        // Finally completed
                        if (fileList.length == completedFileList) {
                            console.log('All files uploaded successfully.');
                            filesSelector.value = '';
                            completedFileList = 0;
                        }
                    }
                }
            });
        }

    </script>
    @endpush
</div>
