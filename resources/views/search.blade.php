@section('extraScripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
@endsection
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 dark:bg-gray-800 border-gray-200">
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
                              action="{{ route('search') }}" class="space-y-6">
                            <div>
                                @csrf
                                <div>
                                    <label for="search"
                                           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Gebruik
                                        de scanfunctie voor EAN13 of SSCC - of vul pakbonnummer in 'SPS-00000000'</label>
                                    <input type="text" id="barcode-input" name="barcode-input"
                                           class="ssccInput"
                                           required autofocus>
                                </div>
                                @error('barcode')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <button type="submit"
                                        class="ssccButton">
                                    Zoeken
                                </button>
                                <div class="mb-4 mt-4 max-w-2xl mx-auto sm:px-1 lg:px-2" id="camera-preview"></div>
                                <canvas class="drawingBuffer" style="display: none"></canvas>
                            </div>

                            <div>

                            </div>
                        </form>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                // Function to initialize QuaggaJS
                                function initializeQuagga() {
                                    Quagga.init({
                                        inputStream: {
                                            name: "Live",
                                            type: "LiveStream",
                                            target: document.querySelector('#camera-preview'), // Attach the camera preview to this element
                                            constraints: {
                                                width: 640,
                                                height: 200,
                                                facingMode: "environment" // Use the rear camera
                                            }
                                        },
                                        decoder: {
                                            readers: ["code_128_reader", "ean_reader", "upc_reader"],
                                            debug: {
                                                drawBoundingBox: true,
                                                drawScanline: true
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
                                }

                                window.onload = function () {
                                    initializeQuagga();
                                };

                                // Listen for barcode detection
                                Quagga.onDetected(function (result) {
                                    const barcode = result.codeResult.code;
                                    console.log('Barcode detected:', barcode);

                                    // Populate the input field with the detected barcode
                                    const barcodeInput = document.getElementById('barcode-input');
                                    barcodeInput.value = barcode;

                                    setTimeout(() => {
                                        if (barcode.trim() !== "") {
                                            console.log('Submitting form with barcode:', barcode);
                                            document.getElementById('barcode-form').submit();
                                        } else {
                                            console.error('Barcode input is empty');
                                        }
                                    }, 500); // Delay submission by 0.5 seconds to allow the user to see the barcode
                                });

                                // Stop the camera when the form is submitted
                                document.getElementById('barcode-form').addEventListener('submit', () => {
                                    Quagga.stop(); // Stop the camera
                                });
                            });
                        </script>

                    </div>


                </div>
            </div>
        </div>
    </div>

</x-app-layout>
