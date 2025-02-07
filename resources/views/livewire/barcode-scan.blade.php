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


    <form method="POST" action="{{ route('scan-barcode') }}" class="space-y-6">
        <div>
            @csrf
            <label for="barcode" class="block text-sm font-medium text-gray-700">
                Barcode
            </label>
            <div class="mt-1">
                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('barcode') border-red-500 @enderror"
                       placeholder="Enter barcode" autofocus>
            </div>
            <div>
                @error('barcode')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit"
                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-red-900 bg-blue-100 border border-transparent rounded-md hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500">
                    Process Barcode
                </button>
            </div>
        </div>


    </form>

</div>
