@section('scannerScripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@6/dist/tesseract.min.js'></script>
    <script>
        // Create a namespace for our scanner
        window.BarcodeScanner = {
            isInitialized: false,
            worker: null,
            validBarcodes: [],
            isProcessing: false,
            failedAttempts: 0,
            MAX_FAILED_ATTEMPTS: 3,
            noDetectionTimeout: null,
            lastSuccessTime: 0,
            lastDetectionAttempt: 0,
            originalConsoleLog: null,
            originalConsoleInfo: null,

            // Utility function to check if we're on a scanner page
            isOnScannerPage() {
                return document.getElementById('camera-preview') !== null;
            },

            // Setup console filtering to suppress asm.js messages
            setupConsoleFiltering() {
                // Store the original console methods
                this.originalConsoleLog = console.log;
                this.originalConsoleInfo = console.info;

                // Override console.log to filter out asm.js compilation messages
                console.log = (...args) => {
                    const messageStr = args.join(' ');
                    if ((typeof messageStr === 'string' &&
                            messageStr.includes('asm.js') &&
                            (messageStr.includes('compiled') || messageStr.includes('compiling'))) ||
                        (args[0] && args[0].status && args[0].status.toString().includes('asm.js'))) {
                        return; // Don't log this message
                    }
                    this.originalConsoleLog.apply(console, args);
                };

                // Do the same for console.info
                console.info = (...args) => {
                    const messageStr = args.join(' ');
                    if ((typeof messageStr === 'string' &&
                            messageStr.includes('asm.js') &&
                            (messageStr.includes('compiled') || messageStr.includes('compiling'))) ||
                        (args[0] && args[0].status && args[0].status.toString().includes('asm.js'))) {
                        return;
                    }
                    this.originalConsoleInfo.apply(console, args);
                };
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

                // Make sure we're on a scanner page
                if (!this.isOnScannerPage()) {
                    console.log('Not on scanner page, skipping initialization');
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
                            // Filter asm.js messages
                            if (!message.status || !message.status.includes('asm.js')) {
                                console.log('Tesseract:', message);
                            }
                        }
                    });

                    // Configure Tesseract for better OCR
                    await this.worker.setParameters({
                        tessedit_char_whitelist: '0123456789()[]{}',  // Only allow digits and some brackets
                        tessedit_pageseg_mode: '6',                   // Assume a single uniform block of text
                        preserve_interword_spaces: '0',               // Don't preserve spaces between words
                        tessedit_ocr_engine_mode: '1'                 // Use LSTM neural network only
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
                try {
                    // Add a manual OCR button
                    this.addManualOCRButton();

                    // Fetch valid barcodes
                    await this.fetchValidBarcodes();

                    // Small delay to ensure DOM is ready
                    await new Promise(resolve => setTimeout(resolve, 100));

                    // Setup camera
                    this.setupQuagga();

                    // Schedule periodic refresh
                    setInterval(() => this.fetchValidBarcodes(), 5 * 60 * 1000);
                } catch (error) {
                    console.error('Error in setup:', error);
                }
            },

            // Add a button to manually trigger OCR processing
            addManualOCRButton() {
                const preview = document.getElementById('camera-preview');
                if (!preview || preview.parentNode === null) {
                    console.log('Camera preview not found, skipping OCR button');
                    return;
                }

                // Check if button already exists
                if (document.getElementById('manual-ocr-btn')) {
                    return;
                }

                // Create button element
                const ocrButton = document.createElement('button');
                ocrButton.id = 'manual-ocr-btn';
                ocrButton.textContent = 'Take Photo (OCR)';
                ocrButton.className = 'px-4 py-2 bg-blue-500 text-white rounded mb-2 mt-2';
                ocrButton.style.display = 'block';
                ocrButton.style.margin = '10px auto';

                // Add click event
                ocrButton.addEventListener('click', () => {
                    this.takePhotoAndProcessWithOCR();
                });

                // Add to DOM after the preview element
                preview.parentNode.insertBefore(ocrButton, preview.nextSibling);
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
                                    const form = document.getElementById('barcode-form');
                                    if (form) form.submit();
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
                    // First check if we're still on a scanner page
                    if (!this.isOnScannerPage()) {
                        console.log('No longer on scanner page, cancelling timeout');
                        return;
                    }

                    const now = Date.now();
                    const lastSuccess = this.lastSuccessTime || 0;
                    const timeSinceLastSuccess = now - lastSuccess;

                    // If no successful scan in 20 seconds
                    if (timeSinceLastSuccess > 20000) {
                        console.log('No barcode detected for an extended period');
                        this.showFeedback('No barcode detected. Switching to OCR mode...', 'info');

                        // Switch to OCR mode for this scan
                        this.takePhotoAndProcessWithOCR();

                        // Continue monitoring after OCR attempt
                        setTimeout(() => this.setupNoDetectionTimeout(), 5000);
                    } else {
                        // Reset the timeout
                        this.setupNoDetectionTimeout();
                    }
                }, 20000); // Check every 20 seconds
            },

            // Method to take a photo from the active video stream and process with OCR
            async takePhotoAndProcessWithOCR() {
                // Check if we're still on a scanner page
                if (!this.isOnScannerPage()) {
                    console.log('No longer on scanner page, cancelling OCR processing');
                    return;
                }

                // Get the video element that Quagga is using
                const videoElement = document.querySelector('#camera-preview video');
                if (!videoElement || !videoElement.videoWidth) {
                    console.error('Cannot access video stream for OCR processing');
                    return;
                }

                try {
                    // Create a canvas the same size as the video
                    const canvas = document.createElement('canvas');
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    const ctx = canvas.getContext('2d');

                    // Draw the current video frame to the canvas
                    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                    // Convert to image data URL
                    const imageUrl = canvas.toDataURL('image/png');

                    // Show visual feedback
                    this.showFeedback('Processing with OCR...', 'info');

                    // First try QuaggaJS on the still image
                    console.log('Trying still image with QuaggaJS');
                    const quaggaResult = await new Promise((resolve) => {
                        Quagga.decodeSingle({
                            decoder: {
                                readers: ["code_128_reader", "ean_reader", "upc_reader"],
                                multiple: false
                            },
                            locate: true,
                            src: imageUrl
                        }, function(result) {
                            if (result && result.codeResult) {
                                resolve(result);
                            } else {
                                resolve(null);
                            }
                        });
                    });

                    // If a barcode is detected
                    if (quaggaResult) {
                        const code = quaggaResult.codeResult.code;
                        const format = quaggaResult.codeResult.format || 'unknown';
                        console.log(`QuaggaJS found ${format} barcode on still image:`, code);
                        await this.processDetectedCode(code, 'still-image-barcode');
                        return;
                    }

                    // If QuaggaJS failed, try OCR with preprocessing
                    console.log('Starting OCR processing on captured image');

                    // Preprocess the image to improve OCR accuracy
                    const enhancedCanvas = document.createElement('canvas');
                    enhancedCanvas.width = canvas.width;
                    enhancedCanvas.height = canvas.height;
                    const enhancedCtx = enhancedCanvas.getContext('2d');

                    // Draw the original image
                    enhancedCtx.drawImage(canvas, 0, 0);

                    // Get image data for processing
                    const imageData = enhancedCtx.getImageData(0, 0, enhancedCanvas.width, enhancedCanvas.height);
                    const data = imageData.data;

                    // Apply contrast enhancement and binarization
                    for (let i = 0; i < data.length; i += 4) {
                        // Convert to grayscale
                        const avg = (data[i] * 0.3 + data[i + 1] * 0.59 + data[i + 2] * 0.11);

                        // Apply threshold (binarization)
                        const newValue = avg > 128 ? 255 : 0;

                        // Set all RGB channels to the new value
                        data[i] = newValue;     // Red
                        data[i + 1] = newValue; // Green
                        data[i + 2] = newValue; // Blue
                        data[i + 3] = 255;      // Alpha (fully opaque)
                    }

                    // Put the modified image data back
                    enhancedCtx.putImageData(imageData, 0, 0);

                    // Get the processed image URL
                    const enhancedImageUrl = enhancedCanvas.toDataURL('image/png');

                    // Show the enhanced image for debugging (optional, can be removed in production)
                    const debugEnhancedImg = document.createElement('img');
                    debugEnhancedImg.src = enhancedImageUrl;
                    debugEnhancedImg.style.position = 'fixed';
                    debugEnhancedImg.style.top = '170px';
                    debugEnhancedImg.style.right = '10px';
                    debugEnhancedImg.style.maxWidth = '150px';
                    debugEnhancedImg.style.border = '2px solid blue';
                    debugEnhancedImg.style.zIndex = '9999';
                    debugEnhancedImg.style.display = 'none'; // Set to 'block' to debug
                    document.body.appendChild(debugEnhancedImg);

                    // Auto-remove debug image after 5 seconds
                    setTimeout(() => {
                        if (debugEnhancedImg.parentNode) debugEnhancedImg.parentNode.removeChild(debugEnhancedImg);
                    }, 5000);

                    // Run OCR on both the original and enhanced images
                    const originalOcrResult = await this.worker.recognize(imageUrl);
                    console.log('Original OCR complete, result:', originalOcrResult);

                    const enhancedOcrResult = await this.worker.recognize(enhancedImageUrl);
                    console.log('Enhanced OCR complete, result:', enhancedOcrResult);

                    // Choose the result with more confidence or more digits
                    let ocrResult = originalOcrResult;

                    // Extract digits from both results
                    const originalDigits = originalOcrResult.data.text.replace(/[^\d]/g, '');
                    const enhancedDigits = enhancedOcrResult.data.text.replace(/[^\d]/g, '');

                    // Use the result with more digits
                    if (enhancedDigits.length > originalDigits.length) {
                        console.log('Using enhanced OCR result (more digits)');
                        ocrResult = enhancedOcrResult;
                    } else if (enhancedOcrResult.data.confidence > originalOcrResult.data.confidence + 10) {
                        console.log('Using enhanced OCR result (higher confidence)');
                        ocrResult = enhancedOcrResult;
                    } else {
                        console.log('Using original OCR result');
                    }

                    // Extract numbers with better handling
                    console.log('Raw OCR text:', ocrResult.data.text);

                    // First, try to find patterns that resemble barcodes
                    const eanPattern = /\d{13}/g;
                    const ssccPattern = /\d{18}/g;
                    const anyDigits = /\d+/g;

                    // Look for SSCC-18 patterns first (prioritizing longer codes)
                    const ssccMatches = ocrResult.data.text.match(ssccPattern) || [];
                    const eanMatches = ocrResult.data.text.match(eanPattern) || [];

                    if (ssccMatches.length > 0) {
                        // Found SSCC-18 patterns
                        console.log('Found SSCC-18 pattern:', ssccMatches[0]);
                        numbers = ssccMatches[0];
                    } else if (eanMatches.length > 0) {
                        // Found EAN-13 patterns
                        console.log('Found EAN-13 pattern:', eanMatches[0]);
                        numbers = eanMatches[0];
                    } else {
                        // No exact patterns found, extract all digits
                        const allDigits = ocrResult.data.text.replace(/[^\d]/g, '');
                        console.log('All digits extracted:', allDigits);

                        // If we have exactly 18 digits, treat as SSCC
                        if (allDigits.length === 18) {
                            console.log('Extracted exactly 18 digits (SSCC)');
                            numbers = allDigits;
                        }
                        // If we have exactly 13 digits, treat as EAN
                        else if (allDigits.length === 13) {
                            console.log('Extracted exactly 13 digits (EAN)');
                            numbers = allDigits;
                        }
                        // If we have more than 18 digits, try to extract the first 18 or 13
                        else if (allDigits.length > 18) {
                            console.log('More than 18 digits found, extracting prefix');
                            if (allDigits.substring(0, 18).match(/^\d{18}$/)) {
                                numbers = allDigits.substring(0, 18);
                                console.log('Extracted first 18 digits as SSCC:', numbers);
                            } else if (allDigits.substring(0, 13).match(/^\d{13}$/)) {
                                numbers = allDigits.substring(0, 13);
                                console.log('Extracted first 13 digits as EAN:', numbers);
                            } else {
                                // Get the longest sequence of digits
                                const allNumbers = allDigits.match(anyDigits) || [];
                                numbers = allNumbers.reduce((longest, current) =>
                                    current.length > longest.length ? current : longest, '');
                                console.log('Using longest number sequence:', numbers);
                            }
                        } else {
                            // Get the longest sequence of digits
                            const cleanedText = ocrResult.data.text
                                .replace(/[^\w\s]/g, '')  // Remove non-alphanumeric except spaces
                                .replace(/[a-zA-Z]/g, ''); // Remove letters

                            console.log('Cleaned OCR text:', cleanedText);

                            // Extract all digit sequences
                            const allNumbers = cleanedText.match(anyDigits) || [];
                            console.log('All number sequences found:', allNumbers);

                            if (allNumbers.length > 0) {
                                // Get the longest sequence of digits
                                numbers = allNumbers.reduce((longest, current) =>
                                    current.length > longest.length ? current : longest, '');

                                console.log('Using longest number sequence:', numbers);
                            } else {
                                // Fallback to the old method
                                numbers = ocrResult.data.text.replace(/[^\d()]/g, '');

                                // Handle (00) prefix for SSCC
                                if (numbers.startsWith('(00)')) {
                                    numbers = numbers.substring(4);
                                }

                                // Remove any remaining parentheses
                                numbers = numbers.replace(/[()]/g, '');
                            }
                        }
                    }

                    console.log('Final processed numbers:', numbers);

                    if (numbers.length > 0) {
                        await this.processDetectedCode(numbers, 'ocr');
                    } else {
                        this.showFeedback('OCR could not detect any numbers. Try taking a clearer picture.', 'warning');
                    }
                } catch (error) {
                    console.error('Error processing image with OCR:', error);
                    this.showFeedback('Error processing image. Please try again.', 'error');
                }
            },

            // Method to provide visual feedback to the user
            showFeedback(message, type = 'info') {
                // First check if we're still on a scanner page
                if (!this.isOnScannerPage()) {
                    console.log('No longer on scanner page, cancelling feedback');
                    return;
                }

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
                    if (feedbackEl) feedbackEl.style.opacity = '0';
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
                        multiple: false  // Only detect one barcode at a time
                    },
                    locate: true,
                    frequency: 10  // Increase scan frequency (process 10 frames per second)
                }, (err) => {
                    if (err) {
                        console.error('Error initializing Quagga:', err);
                        return;
                    }

                    console.log('Quagga initialized successfully');
                    Quagga.start();

                    // Add barcode detection event listener
                    Quagga.onDetected((result) => {
                        // Track last detection time
                        this.lastDetectionAttempt = Date.now();

                        // Check confidence score
                        const confidence = result.codeResult.confidence || 0;

                        if (confidence > 0.75) {
                            console.log(`Barcode detected with high confidence (${confidence.toFixed(2)}):`, result.codeResult.code);
                            this.processDetectedCode(result.codeResult.code, 'live-barcode');
                        } else {
                            console.log(`Barcode detected but low confidence (${confidence.toFixed(2)}):`, result.codeResult.code);
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

            // Cleanup function for Livewire page changes
            cleanup() {
                console.log('Performing scanner cleanup');

                // Clear all timeouts
                if (this.noDetectionTimeout) {
                    clearTimeout(this.noDetectionTimeout);
                    this.noDetectionTimeout = null;
                }

                // Remove any feedback elements
                const feedbackEl = document.getElementById('barcode-feedback');
                if (feedbackEl && feedbackEl.parentNode) {
                    feedbackEl.parentNode.removeChild(feedbackEl);
                }

                // Remove OCR button
                const ocrButton = document.getElementById('manual-ocr-btn');
                if (ocrButton && ocrButton.parentNode) {
                    ocrButton.parentNode.removeChild(ocrButton);
                }

                // Terminate workers
                if (this.worker) {
                    try {
                        this.worker.terminate();
                    } catch (e) {
                        console.error('Error terminating Tesseract worker:', e);
                    }
                }

                // Stop Quagga
                if (typeof Quagga !== 'undefined') {
                    try {
                        Quagga.stop();
                    } catch (e) {
                        console.error('Error stopping Quagga:', e);
                    }
                }

                // Restore original console methods
                this.restoreConsole();

                // Reset all state
                this.isInitialized = false;
                this.worker = null;
                this.isProcessing = false;
                this.failedAttempts = 0;
                this.lastSuccessTime = 0;
                this.lastDetectionAttempt = 0;

                console.log('Scanner cleanup completed');
            },

            // Method to safely initialize the scanner
            safeInitialize() {
                // Only initialize if we're on a scanner page
                if (this.isOnScannerPage() && !this.isInitialized) {
                    console.log('On scanner page, initializing...');
                    this.initializeScanner();
                } else {
                    console.log('Not on scanner page or already initialized, skipping initialization');
                }
            }
        };

        // Function to check if dependencies are loaded
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

        // Try to initialize scanner with retries
        function tryInitialize(retryCount = 0, maxRetries = 20) {
            // Skip if not on scanner page
            if (!window.BarcodeScanner.isOnScannerPage()) {
                console.log('Not on scanner page, skipping initialization');
                return;
            }

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
                    setTimeout(() => tryInitialize(retryCount + 1, maxRetries), 300);
                } else {
                    console.error('Failed to load dependencies after multiple attempts');
                }
                return;
            }

            // Try to initialize the scanner
            window.BarcodeScanner.safeInitialize();
        }

        // Register key event listeners

        // Clean up on navigation
        document.addEventListener('livewire:navigating', () => {
            console.log('Navigation detected, cleaning up scanner');
            window.BarcodeScanner.cleanup();
        });

        // Setup initialization methods
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, attempting initialization');
            tryInitialize();
        });

        // Listen for Livewire events
        document.addEventListener('livewire:load', () => {
            console.log('Livewire loaded, attempting initialization');
            tryInitialize();
        });

        document.addEventListener('livewire:update', () => {
            console.log('Livewire updated, checking scanner status');
            if (window.BarcodeScanner.isOnScannerPage() && !window.BarcodeScanner.isInitialized) {
                console.log('Scanner element found after Livewire update, initializing');
                tryInitialize();
            }
        });

        // Immediate check
        (() => {
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                console.log('Document already interactive or complete, attempting immediate initialization');
                tryInitialize();
            }
        })();
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
                                           required>
                                </div>
                                @error('barcode')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <button type="submit"
                                        class="ssccButton">
                                    Zoeken
                                </button>
                                <div class="mb-6 mt-6 max-w-fit max-h-dvh mx-auto sm:px-1 lg:px-2" id="camera-preview"></div>
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
