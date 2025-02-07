@section('extraScripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
@endsection
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Scan') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div>
                        @if (session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                                 role="alert">
                                <span class="block sm:inline">{{ session('error') }}</span>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div
                                    class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4"
                                    role="alert">
                                <span class="block sm:inline">{{ session('warning') }}</span>
                            </div>
                        @endif


                        <form method="POST" id="barcode-form" name="barcode-form"
                              action="{{ route('scan-barcode') }}" class="space-y-6">
                            <div>
                                @csrf
                                <label for="barcode" class="block text-sm font-medium text-gray-700">
                                    Barcode
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="barcode-input" id="barcode-input"
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('barcode') border-red-500 @enderror"
                                           placeholder="Enter barcode" autofocus readonly>
                                </div>
                                @error('barcode')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div id="camera-preview"></div>


                            </div>

                            <div>
                                <button type="submit"
                                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-red-900 bg-blue-100 border border-transparent rounded-md hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500">
                                    Process Barcode
                                </button>
                            </div>
                        </form>
                        <script>
                            // Initialize QuaggaJS
                            Quagga.init({
                                inputStream: {
                                    name: "Live",
                                    type: "LiveStream",
                                    target: document.querySelector('#camera-preview'), // Attach the camera preview to this element
                                    constraints: {
                                        facingMode: "environment" // Use the rear camera
                                    }
                                },
                                decoder: {
                                    // Supported barcode formats
                                    readers: ["code_128_reader", "ean_reader", "upc_reader"
                                    ],
                                    debug: {
                                        drawBoundingBox: false,
                                        drawScanline: false
                                    }
                                }
                            }, function (err) {
                                if (err) {
                                    console.error('Error initializing QuaggaJS:', err);
                                    return;
                                }
                                console.log('QuaggaJS initialized successfully');
                                Quagga.start(); // Start the barcode scanner
                            });

                            // Listen for barcode detection
                            Quagga.onDetected(function (result) {
                                const barcode = result.codeResult.code;
                                console.log('Barcode detected:', barcode);

                                // Populate the input field with the detected barcode
                                const barcodeInput = document.getElementById('barcode-input');
                                barcodeInput.value = barcode;

                                setTimeout(() => {
                                    if (barcode.value.trim() !== "") {
                                        console.log('Submitting form with barcode:', barcode.value);
                                        // Stop Quagga before submitting the form
                                        Quagga.stop();
                                        document.getElementById('barcode-form').submit();
                                    } else {
                                        console.error('Barcode input is empty');
                                    }
                                }, 1000); // Delay submission by 1 second to allow the user to see the barcode
                            });

                            // Stop the camera when the form is submitted
                            document.getElementById('barcode-form').addEventListener('submit', () => {
                                Quagga.stop(); // Stop the camera
                            });
                        </script>

                    </div>


                </div>
            </div>
        </div>
    </div>
</x-app-layout>
