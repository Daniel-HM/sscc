@section('scannerScripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@6/dist/tesseract.min.js'></script>
    <script>
        // Create a namespace for our scanner
        window.BarcodeScanner = window.BarcodeScanner || {
            isInitialized: false,
            worker: null,
            validBarcodes: [],
            isProcessing: false,
            failedAttempts: 0,
            MAX_FAILED_ATTEMPTS: 3,
            TEST_MODE: false,

            // Setup console filtering to suppress asm.js messages
            setupConsoleFiltering() {
                // Store the original console methods
                const originalConsoleLog = console.log;
                const originalConsoleInfo = console.info;

                // Override console.log to filter out asm.js compilation messages
                console.log = function(...args) {
                    // Convert args to string for easy checking
                    const messageStr = args.join(' ');
                    // Filter out asm.js compilation messages
                    if ((typeof messageStr === 'string' &&
                            (messageStr.includes('asm.js') &&
                                (messageStr.includes('compiled') || messageStr.includes('compiling')))) ||
                        (args[0] && args[0].status &&
                            args[0].status.toString().includes('asm.js'))) {
                        return; // Don't log this message
                    }
                    // Pass through all other messages
                    originalConsoleLog.apply(console, args);
                };

                // Do the same for console.info which might also be used
                console.info = function(...args) {
                    const messageStr = args.join(' ');
                    if ((typeof messageStr === 'string' &&
                            (messageStr.includes('asm.js') &&
                                (messageStr.includes('compiled') || messageStr.includes('compiling')))) ||
                        (args[0] && args[0].status &&
                            args[0].status.toString().includes('asm.js'))) {
                        return;
                    }
                    originalConsoleInfo.apply(console, args);
                };

                // Store original methods for cleanup
                this.originalConsoleLog = originalConsoleLog;
                this.originalConsoleInfo = originalConsoleInfo;
            },

            // Restore original console methods on cleanup
            restoreConsole() {
                if (this.originalConsoleLog) {
                    console.log = this.originalConsoleLog;
                }
                if (this.originalConsoleInfo) {
                    console.info = this.originalConsoleInfo;
                }
            },

            // Main initialization function
            async initializeScanner() {
                if (this.isInitialized) {
                    console.log('Scanner already initialized');
                    return;
                }

                console.log('Starting scanner initialization');
                this.isInitialized = true;

                // Initialize properties for tracking scan success/failure
                this.lastSuccessTime = 0;
                this.lastDetectionAttempt = 0;
                this.noDetectionTimeout = null;
                this.failedAttempts = 0;

                // Setup console filtering to suppress asm.js messages
                this.setupConsoleFiltering();

                // Initialize Tesseract
                try {
                    this.worker = await Tesseract.createWorker('eng', 1, {
                        logger: message => {
                            // Additional filtering at the source
                            if (!message.status ||
                                (!message.status.includes('asm.js'))) {
                                console.log('Tesseract:', message);
                            }
                        }
                    });
                    console.log('Tesseract worker initialized successfully');
                } catch (error) {
                    console.error('Error initializing Tesseract:', error);
                    this.isInitialized = false;
                    this.restoreConsole();
                    return;
                }

                await this.setupUI();
            },

            // UI Setup
            async setupUI() {
                await this.fetchValidBarcodes();

                // Small delay to ensure DOM is ready
                await new Promise(resolve => setTimeout(resolve, 100));

                if (this.TEST_MODE) {
                    // Test mode - setup file input and refresh button
                    this.setupFileInput();
                    this.setupRefreshButton();
                } else {
                    // Live mode - setup camera
                    this.setupQuagga();
                }

                // Schedule periodic refresh
                setInterval(() => this.fetchValidBarcodes(), 5 * 60 * 1000);
            },

            // Fetch valid barcodes from server
            async fetchValidBarcodes() {
                try {
                    const response = await fetch('/valid-barcodes', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        this.validBarcodes = await response.json();
                        console.log('Valid codes refreshed, count:', this.validBarcodes.length);
                    }
                } catch (error) {
                    console.error('Error fetching valid barcodes:', error);
                }
            },

            setupFileInput() {
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.accept = 'image/*';
                fileInput.className = 'mb-4';

                const preview = document.querySelector('#camera-preview');
                if (preview) {
                    preview.before(fileInput);
                    fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
                }
            },

            setupRefreshButton() {
                const fileInput = document.querySelector('input[type="file"]');
                if (!fileInput) return;

                const refreshButton = document.createElement('button');
                refreshButton.textContent = 'Refresh Codes';
                refreshButton.className = 'mb-4 ml-2 px-4 py-2 bg-blue-500 text-white rounded';
                refreshButton.onclick = () => this.fetchValidBarcodes();
                fileInput.after(refreshButton);
            },

            async handleFileSelect(e) {
                console.log('File selected, beginning processing');

                if (e.target.files && e.target.files[0]) {
                    const imageFile = e.target.files[0];
                    console.log('Processing file:', imageFile.name);

                    const imageUrl = URL.createObjectURL(imageFile);
                    console.log('Created image URL');

                    try {
                        // First try QuaggaJS
                        console.log('Starting QuaggaJS detection');
                        const quaggaResult = await new Promise((resolve, reject) => {
                            Quagga.decodeSingle({
                                inputStream: {
                                    size: 800,
                                    singleChannel: false,
                                    type: "ImageStream"
                                },
                                decoder: {
                                    readers: ["code_128_reader", "ean_reader", "upc_reader"]
                                },
                                locate: true,
                                src: imageUrl
                            }, function(result) {
                                console.log('QuaggaJS processing complete');
                                if (result && result.codeResult) {
                                    console.log('QuaggaJS found barcode:', result.codeResult.code);
                                    resolve(result);
                                } else {
                                    console.log('QuaggaJS could not find barcode, will try OCR');
                                    resolve(null);
                                }
                            });
                        });

                        // If no barcode detected, try OCR
                        if (!quaggaResult) {
                            console.log('Starting OCR processing');
                            const ocrResult = await this.worker.recognize(imageUrl);
                            console.log('OCR complete, result:', ocrResult);

                            // Extract numbers and handle SSCC format
                            let numbers = ocrResult.data.text.replace(/[^\d()]/g, '');
                            console.log('Raw extracted numbers:', numbers);

                            // Handle (00) prefix for SSCC
                            if (numbers.startsWith('(00)')) {
                                numbers = numbers.substring(4);
                            }

                            // Remove any remaining parentheses
                            numbers = numbers.replace(/[()]/g, '');
                            console.log('Processed numbers:', numbers);

                            if (numbers.length > 0) {
                                await this.processDetectedCode(numbers, 'ocr');
                            }
                        } else {
                            await this.processDetectedCode(quaggaResult.codeResult.code, 'barcode');
                        }

                    } catch (error) {
                        console.error('Error processing image:', error);
                    } finally {
                        URL.revokeObjectURL(imageUrl);
                        console.log('Cleaned up image URL');
                    }
                }
            },

            async processDetectedCode(code, source = 'barcode') {
                if (this.isProcessing) return;
                this.isProcessing = true;

                try {
                    console.log(`Code detected from ${source}:`, code);

                    // Handle (00) prefix for SSCC if present
                    if (code.startsWith('(00)')) {
                        code = code.substring(4);
                    }
                    // Clean the code
                    code = code.replace(/[()]/g, '');

                    // Determine if it's an EAN-13 or SSCC-18
                    const isEAN = code.length === 13;
                    const isSSCC = code.length === 18;

                    if (isEAN || isSSCC) {
                        // Find in validBarcodes array
                        const foundBarcode = this.validBarcodes.find(b =>
                            b.code === code &&
                            b.type === (isEAN ? 'ean' : 'sscc')
                        );

                        if (foundBarcode) {
                            console.log(`Valid ${isEAN ? 'EAN' : 'SSCC'} found:`, code);
                            // Reset failed attempts on success
                            this.failedAttempts = 0;
                            this.lastSuccessTime = Date.now();

                            const barcodeInput = document.getElementById('barcode-input');
                            if (barcodeInput) {
                                barcodeInput.value = code;
                                // Submit the form after a brief delay
                                setTimeout(() => {
                                    document.getElementById('barcode-form').submit();
                                }, 500);
                            }
                        } else {
                            console.log(`${isEAN ? 'EAN' : 'SSCC'} not found in database:`, code);
                            this.failedAttempts++;
                            this.showFeedback('Invalid barcode: Not found in database', 'warning');
                        }
                    } else {
                        console.log('Invalid number length, not an EAN-13 or SSCC-18:', code);
                        this.failedAttempts++;
                        this.showFeedback(`Invalid barcode format: Expected EAN-13 or SSCC-18, got length ${code.length}`, 'warning');
                    }

                    // Check if we've exceeded the maximum failed attempts
                    if (this.failedAttempts >= this.MAX_FAILED_ATTEMPTS) {
                        console.log(`Maximum failed attempts (${this.MAX_FAILED_ATTEMPTS}) reached`);
                        this.showFeedback('Too many failed attempts. Please try a different barcode or method.', 'error');
                        // Reset the counter after showing feedback
                        this.failedAttempts = 0;
                    }
                } finally {
                    this.isProcessing = false;
                }
            },

            // Add a method to handle "no detection" scenarios
            setupNoDetectionTimeout() {
                // Clear any existing timeout
                if (this.noDetectionTimeout) {
                    clearTimeout(this.noDetectionTimeout);
                }

                // Set a timeout to check if no barcodes have been detected for a while
                this.noDetectionTimeout = setTimeout(() => {
                    const now = Date.now();
                    const lastSuccess = this.lastSuccessTime || 0;
                    const timeSinceLastSuccess = now - lastSuccess;

                    // If no successful scan in 20 seconds and camera is active
                    if (timeSinceLastSuccess > 7000 && !this.TEST_MODE) {
                        console.log('No barcode detected for an extended period');
                        this.showFeedback('No barcode detected. Try adjusting the camera position or lighting.', 'info');

                        // Reset the timeout
                        this.setupNoDetectionTimeout();
                    }
                }, 20000); // Check every 20 seconds
            },

            // Method to provide visual feedback to the user
            showFeedback(message, type = 'info') {
                console.log(`Feedback (${type}): ${message}`);

                // Create or get feedback element
                let feedbackEl = document.getElementById('barcode-feedback');
                if (!feedbackEl) {
                    feedbackEl = document.createElement('div');
                    feedbackEl.id = 'barcode-feedback';
                    feedbackEl.style.position = 'absolute';
                    feedbackEl.style.bottom = '20px';
                    feedbackEl.style.left = '50%';
                    feedbackEl.style.transform = 'translateX(-50%)';
                    feedbackEl.style.padding = '10px 20px';
                    feedbackEl.style.borderRadius = '4px';
                    feedbackEl.style.fontSize = '14px';
                    feedbackEl.style.fontWeight = 'bold';
                    feedbackEl.style.zIndex = '1000';
                    feedbackEl.style.transition = 'opacity 0.3s ease';

                    const previewEl = document.getElementById('camera-preview');
                    if (previewEl && previewEl.parentNode) {
                        previewEl.parentNode.style.position = 'relative';
                        previewEl.parentNode.appendChild(feedbackEl);
                    } else {
                        document.body.appendChild(feedbackEl);
                    }
                }

                // Set colors based on message type
                switch (type) {
                    case 'error':
                        feedbackEl.style.backgroundColor = 'rgba(220, 53, 69, 0.9)';
                        feedbackEl.style.color = 'white';
                        break;
                    case 'warning':
                        feedbackEl.style.backgroundColor = 'rgba(255, 193, 7, 0.9)';
                        feedbackEl.style.color = 'black';
                        break;
                    case 'success':
                        feedbackEl.style.backgroundColor = 'rgba(40, 167, 69, 0.9)';
                        feedbackEl.style.color = 'white';
                        break;
                    default: // info
                        feedbackEl.style.backgroundColor = 'rgba(23, 162, 184, 0.9)';
                        feedbackEl.style.color = 'white';
                }

                // Set content and show
                feedbackEl.textContent = message;
                feedbackEl.style.opacity = '1';

                // Auto-hide after 3 seconds
                setTimeout(() => {
                    feedbackEl.style.opacity = '0';
                }, 3000);
            },

            setupQuagga() {
                console.log('Setting up camera with QuaggaJS');
                const preview = document.getElementById('camera-preview');

                if (!preview) {
                    console.error('Camera preview element not found');
                    return;
                }

                // Check if camera access is available
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    console.error('getUserMedia not supported in this browser');
                    this.fallbackToFileUpload();
                    return;
                }

                // Initialize Quagga with live camera feed
                Quagga.init({
                    inputStream: {
                        name: "Live",
                        type: "LiveStream",
                        target: preview,
                        constraints: {
                            width: { min: 640 },
                            height: { min: 480 },
                            facingMode: "environment" // Use back camera on mobile
                        },
                    },
                    locator: {
                        patchSize: "medium",
                        halfSample: true
                    },
                    numOfWorkers: navigator.hardwareConcurrency || 4,
                    decoder: {
                        readers: ["code_128_reader", "ean_reader", "upc_reader"],
                        multiple: false,  // Only detect one barcode at a time
                        debug: {
                            showCanvas: true,
                            showPatches: true,
                            showFoundPatches: true,
                            showSkeleton: true,
                            showLabels: true,
                            showPatchLabels: true,
                            showRemainingPatchLabels: true,
                        }
                    },
                    locate: true,
                    frequency: 10  // Increase scan frequency (process 10 frames per second)
                }, (err) => {
                    if (err) {
                        console.error('Error initializing Quagga:', err);
                        this.fallbackToFileUpload();
                        return;
                    }

                    console.log('Quagga initialized successfully');
                    Quagga.start();

                    // Track processing for frequency analysis
                    let lastProcessed = Date.now();
                    let frequencyTotal = 0;
                    let frequencyCount = 0;

                    // Add barcode detection event listener
                    Quagga.onDetected((result) => {
                        // Track last detection time
                        this.lastDetectionAttempt = Date.now();

                        // Track processing frequency
                        const now = Date.now();
                        const elapsed = now - lastProcessed;
                        lastProcessed = now;

                        frequencyTotal += elapsed;
                        frequencyCount++;

                        if (frequencyCount >= 10) {
                            const avgFrequency = frequencyTotal / frequencyCount;
                            console.log(`Average processing frequency: ${Math.round(1000/avgFrequency)} fps`);
                            frequencyTotal = 0;
                            frequencyCount = 0;
                        }

                        // Check confidence score
                        if (result.codeResult.confidence > 0.75) {
                            console.log(`Barcode detected with high confidence (${result.codeResult.confidence.toFixed(2)}):`, result.codeResult.code);
                            this.processDetectedCode(result.codeResult.code, 'live-barcode');
                        } else {
                            console.log(`Barcode detected but low confidence (${result.codeResult.confidence.toFixed(2)}):`, result.codeResult.code);
                            // For low confidence, we increment failedAttempts but still try to process
                            this.failedAttempts += 0.5; // Count as half an attempt
                            this.processDetectedCode(result.codeResult.code, 'live-barcode-low-confidence');
                        }
                    });

                    // Setup "no detection" timeout to provide feedback if no barcodes are detected
                    this.setupNoDetectionTimeout();

                    // Add processing hook to track scanning attempts even when no barcode is found
                    Quagga.onProcessed((result) => {
                        // Update last attempt time
                        this.lastDetectionAttempt = Date.now();

                        // Visualize the processed result
                        if (result && result.codeResult && result.codeResult.code) {
                            // Successful detection already handled by onDetected
                        } else if (result && result.boxes) {
                            // Found some boxes but couldn't decode a barcode
                            // This is a "soft" failure - we tried but couldn't decode
                            this.failedAttempts += 0.1; // Count as 1/10 of an attempt
                        }

                        // Check if camera is detecting anything consistently
                        const inactivityPeriod = Date.now() - this.lastDetectionAttempt;
                        if (inactivityPeriod > 5000) { // 5 seconds with no activity
                            console.log("Camera appears to be inactive");
                            this.showFeedback("No camera activity detected. Please ensure camera has proper permissions.", "warning");
                        }
                    });
                });
            },

            fallbackToFileUpload() {
                console.log('Falling back to file upload mode');
                this.TEST_MODE = true;
                this.setupFileInput();
                this.setupRefreshButton();
            },

            // Cleanup function for Livewire page changes
            cleanup() {
                if (this.worker) {
                    this.worker.terminate();
                }
                if (!this.TEST_MODE && Quagga) {
                    Quagga.stop();
                }

                // Restore original console methods
                this.restoreConsole();

                this.isInitialized = false;
                this.worker = null;
            }
        };

        // Function to check if all required dependencies are loaded
        function checkDependencies() {
            if (typeof Quagga === 'undefined') {
                console.warn('QuaggaJS not loaded');
                return false;
            }
            if (typeof Tesseract === 'undefined') {
                console.warn('Tesseract not loaded');
                return false;
            }
            return true;
        }

        // Function to check if the camera element exists
        function cameraElementExists() {
            return document.getElementById('camera-preview') !== null;
        }

        // Function to initialize scanner when dependencies and DOM elements are ready
        function initializeWhenReady(retryCount = 0, maxRetries = 20) {
            console.log(`Initialization attempt ${retryCount + 1}/${maxRetries}`);

            if (window.BarcodeScanner.isInitialized) {
                console.log('Scanner already initialized, skipping initialization');
                return;
            }

            // Check if dependencies are loaded
            const dependenciesLoaded = checkDependencies();
            if (!dependenciesLoaded) {
                if (retryCount < maxRetries) {
                    console.log('Dependencies not loaded yet, retrying in 300ms');
                    setTimeout(() => initializeWhenReady(retryCount + 1, maxRetries), 300);
                } else {
                    console.error('Failed to load dependencies after multiple attempts');
                }
                return;
            }

            // Check if camera element exists
            if (cameraElementExists()) {
                console.log('Camera preview element found, initializing scanner');
                window.BarcodeScanner.initializeScanner();
            } else {
                if (retryCount < maxRetries) {
                    console.log('Camera preview element not found yet, retrying in 300ms');
                    setTimeout(() => initializeWhenReady(retryCount + 1, maxRetries), 300);
                } else {
                    console.warn('Camera preview element not found after multiple attempts');
                    // Fallback to test mode if camera element never appears
                    window.BarcodeScanner.TEST_MODE = true;
                    window.BarcodeScanner.initializeScanner();
                }
            }
        }

        // Initialize scanner when the page loads - use both approaches for maximum compatibility

        // Approach 1: Start checking as soon as the script runs
        (function immediateCheck() {
            console.log('Immediate scanner initialization check');
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initializeWhenReady();
            } else {
                setTimeout(immediateCheck, 100);
            }
        })();

        // Approach 2: Listen for DOM content loaded
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, checking for scanner dependencies and elements');
            initializeWhenReady();
        });

        // Approach 3: Listen for window load (last resort)
        window.addEventListener('load', () => {
            console.log('Window loaded, checking for scanner dependencies and elements');
            if (!window.BarcodeScanner.isInitialized) {
                initializeWhenReady();
            }
        });

        // Approach 4: Also listen for Livewire events
        document.addEventListener('livewire:load', () => {
            console.log('Livewire loaded, checking for scanner dependencies and elements');
            if (!window.BarcodeScanner.isInitialized) {
                initializeWhenReady();
            }
        });

        // Listen for Livewire updates that might add the camera element later
        document.addEventListener('livewire:update', () => {
            console.log('Livewire updated, checking for camera element');
            if (!window.BarcodeScanner.isInitialized && cameraElementExists() && checkDependencies()) {
                console.log('Camera element found after Livewire update, initializing scanner');
                window.BarcodeScanner.initializeScanner();
            }
        });

        // Alpine.js integration if it's being used
        if (typeof window.Alpine !== 'undefined') {
            document.addEventListener('alpine:initialized', () => {
                console.log('Alpine initialized, checking for scanner');
                if (!window.BarcodeScanner.isInitialized) {
                    initializeWhenReady();
                }
            });
        }

        // Clean up on navigation
        document.addEventListener('livewire:navigating', () => {
            console.log('Navigation detected, cleaning up scanner');
            window.BarcodeScanner.cleanup();
        });
    </script>
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
                                        de scanfunctie voor EAN13 of SSCC - of vul pakbonnummer in
                                        'SPS-00000000'</label>
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


                    </div>


                </div>
            </div>
        </div>
    </div>

</x-app-layout>
