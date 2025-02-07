<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner with QuaggaJS</title>
    <!-- Include QuaggaJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
</head>
<body>
    <h1>Scan Barcode</h1>
    <form id="barcode-form" method="POST" action="{{ route('scan-barcode') }}">
        @csrf <!-- Add CSRF token for security -->
        <input type="text" id="barcode-input" name="barcode">
        <button type="submit">Submit</button>
    </form>
    <div id="camera-preview"></div>

    {{-- <script>
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
                readers: ["code_128_reader", "ean_reader", "upc_reader"] // Supported barcode formats
            },
            locate: true,
            src: 'storage/app/public/sscc-barcode.gif'
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
            document.getElementById('barcode-input').value = barcode;

            // Optionally, submit the form automatically
            document.getElementById('barcode-form').submit();
        });

        // Stop the camera when the form is submitted
        document.getElementById('barcode-form').addEventListener('submit', () => {
            Quagga.stop(); // Stop the camera
        });
    </script> --}}
</body>
</html>
