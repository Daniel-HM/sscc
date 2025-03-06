@section('extraScripts')
@endsection
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Upload artikel, leveranciers of voorraad bestand
        </h2>
    </x-slot>
    <section class="dark:bg-gray-800 text-gray-600 body-font overflow-hidden">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-2 text-gray-900 dark:text-gray-100">
                        <form id="uploadForm" method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data">
                            <div>
                                <label for="file" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Enkel .xlsx of .csv toegestaan</label>
                                <input type="file" name="file" id="file" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-96 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            </div>
                            <div>
                                <button type="submit" class="ssccButton">Upload</button>
                            </div>
                            <div id="uploadStatus" class="mt-2"></div>
                        </form>

                        <script>
                            document.getElementById('uploadForm').addEventListener('submit', async function(e) {
                                e.preventDefault();

                                const form = this;
                                const formData = new FormData(form);
                                const statusDiv = document.getElementById('uploadStatus');
                                const submitButton = form.querySelector('button[type="submit"]');

                                // Disable submit button during upload
                                submitButton.disabled = true;
                                statusDiv.textContent = 'Bezig met uploaden...';
                                statusDiv.className = 'mt-2 text-gray-600';

                                try {
                                    const response = await fetch(form.action, {
                                        method: 'POST',
                                        body: formData,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        credentials: 'same-origin'
                                    });

                                    // Add debug logging
                                    console.log('Response status:', response.status);
                                    const responseText = await response.text();
                                    console.log('Raw response:', responseText);

                                    // Try to parse as JSON if possible
                                    let result;
                                    try {
                                        result = JSON.parse(responseText);
                                    } catch (e) {
                                        console.error('JSON parse error:', e);
                                        throw new Error('Invalid JSON response');
                                    }

                                    if (response.ok) {
                                        statusDiv.textContent = result.message;
                                        statusDiv.className = 'mt-2 text-green-600';
                                        form.reset();
                                    } else {
                                        statusDiv.textContent = result.message || 'Er is een fout opgetreden';
                                        statusDiv.className = 'mt-2 text-red-600';
                                    }
                                } catch (error) {
                                    console.error('Upload error:', error);
                                    statusDiv.textContent = 'Er is een fout opgetreden tijdens het uploaden: ' + error.message;
                                    statusDiv.className = 'mt-2 text-red-600';
                                } finally {
                                    submitButton.disabled = false;
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

