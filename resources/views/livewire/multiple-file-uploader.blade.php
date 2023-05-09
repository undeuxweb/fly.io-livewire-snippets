<div>
    <div wire:ignore>
        <input type="file" id="myFiles" multiple wire:loading.attr="disabled">
        <button type="button" id="myButton" wire:click="$refresh">Refresh</button>
    </div>

    {{-- @error('uploads.*') <div class="error">{{ $message }}</div> @enderror --}}

    <p>{{ now() }}</p>

    {{-- @foreach ($uploads as $i => $upl)
        <div class="mt-2 bg-blue-50 rounded-full pt-2 pr-4 pl-4 pb-2">
            <label class="flow-root">
                <div class="float-left">{{ $upl['fileName'] }} / {{ $upl['fileSize'] }}</div>
                <div class="float-right">{{ floor($upl['progress']) }}%</div>
            </label>
            <progress max="100" wire:model="uploads.{{ $i }}.progress"
                class="h-1 w-full bg-neutral-200 dark:bg-neutral-60" />
        </div>
    @endforeach --}}

    @forelse ($files as $i => $file)
        <div wire:key="{{ $i }}">
            {{-- {{ print_r(compact('file')); }} --}}
            <p>{{ $i }}</p>
            @if(isset($file['fileRef']))
                <img src="{{ $file['fileRef']->temporaryUrl() }}" width="100" />
            @endif
            <button type="button" wire:click="deleteFile({{ $i }})">削除</button>
        </div>
    @empty
    @endforelse

    @push('scripts')
    <script type="module">
        const filesSelector = document.querySelector('#myFiles');
        let fileList;
        let chnkStarts = [];
        let completedFileList = 0;
        //let isActive = false;

        /**
         * Change event handler
         * @param {Event} event
         */
        const change = event => {
            fileList = [...filesSelector.files];
            console.log(fileList);

            fileList.forEach((file, index) => {
                @this.call('setFileDetails', index, file.name, file.size);
                chnkStarts[index] = 0;
                livewireUploadChunk(index, file);
            });
        }

        // Add event listener
        filesSelector.addEventListener('change', change);

        const livewireUploadChunk = (index, file) => {
            // End of chunk is start + chunkSize OR file size, whichever is greater
            const chunkEnd = Math.min(chnkStarts[index] + @js($chunkSize), file.size);
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
                if (e.detail.progress == 100) {
                    chnkStarts[index] = Math.min(chnkStarts[index] + @js($chunkSize), file.size);

                    // Incomplete
                    if (chnkStarts[index] < file.size) {
                        let _time = Math.floor((Math.random() * 2000) + 1);
                        console.log('retryChunk: ' + chnkStarts[index] + '<' + file.size);
                        setTimeout(livewireUploadChunk, _time, index, file);
                    }
                    // Completed
                    else {
                        completedFileList ++;
                        console.log('completedFileList: ' + fileList.length + ' / ' + completedFileList);

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
