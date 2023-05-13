<div>
    <p>{{ now() }}</p>
    {{-- <button id="test">setDragAndDrop</button> --}}

    <div>
        <input type="file" id="myFiles" multiple wire:loading.attr="disabled">
        <button type="button" id="myButton" wire:click="$refresh">Refresh</button>

        @error('uploads.*')
            <div class="error text-red-600">{{ $message }}</div>
        @enderror

        <p>{{ now() }}</p>
    </div>

    <div drag-root="reorderFiles">
        @forelse ($files as $i => $file)
            <div wire:key="item-{{ $file['id'] }}" drag-item="{{ $file['id'] }}"
                class="list-item @if ($file['progress'] < 100) opacity-50 @endif">
                <p>
                    INDEX: {{ $i }} ID: {{ $file['id'] }} {{ floor($file['progress']) }}%
                    {{ now() }}
                </p>
                <p>
                    File: {{ $file['originalFileName'] }} ({{ number_format($file['fileSize'] / 1024, 2) . ' KB' }})
                </p>
                @if (isset($file['fileRef']))
                    <img src="{{ $file['fileRef']->temporaryUrl() }}" width="50"
                        wire:key="img-{{ $file['id'] }}" />
                @endif
                <input type="file" drag-item-file="{{ $file['id'] }}" wire:loading.attr="disabled">
                <button type="button" wire:click="deleteFile({{ $file['id'] }})">削除</button>
            </div>
        @empty
        @endforelse
    </div>

    {{-- <div class='list-hidden'></div> --}}
    {{-- <div id="list">
        <div class="list-item" drag-item="1" draggable=true>
            Item 1
        </div>
        <div class="list-item" drag-item="2" draggable=true>
            Item 2
        </div>
        <div class="list-item" drag-item="3" draggable=true>
            Item 3
        </div>
        <div class="list-item" drag-item="4" draggable=true>
            Item 4
        </div>
    </div> --}}

    @push('scripts')
        <script type="module">
            // Select the file input
            const multiFileSelector = document.querySelector('#myFiles');

            const fileSelector = document.querySelector('[drag-item-file]');

            const root = document.querySelector('[drag-root]')

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
            const change = e => {
                // Get file ID
                const fileId = e.target.getAttribute('drag-item-file');

                // Get file list
                fileList = [...e.target.files];

                // Check if we can upload more files
                @this.call('resetUploadFiles', fileList.length).then(success => {
                    if (success) {
                        fileList.forEach((file, index) => {
                            // Detect if uploading a new file or updating an existing file
                            const func = fileId ? @this.call('setUpdateFileDetails', 0, file.name, file.size, fileId)
                                                : @this.call('setFileDetails', index, file.name, file.size);
                            // Set file details
                            func.then(() => {
                                // Reset chunk start
                                chnkStarts[index] = 0;

                                // Start uploading
                                livewireUploadChunk(index, file);
                            });
                        });
                    } else {
                        multiFileSelector.value = '';
                    }
                });
            }

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
                }, e => {
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
                                multiFileSelector.value = '';
                                completedFileList = 0;
                            }
                        }
                    }
                });
            }

            // Add event listener register file
            multiFileSelector.addEventListener('change', change);

            // Add event listener update file
            root.addEventListener('change', e => {
                if (e.target.hasAttribute('drag-item-file')) {
                    change(e);
                }
            });
        </script>

        @vite(['resources/css/sortable.css', 'resources/js/sortableJs.js'])
    @endpush
</div>
