@section('barcodeCreator')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.ean-upc.min.js"></script>
@endsection
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

        </h2>
    </x-slot>
    <section class="dark:bg-gray-800 text-gray-600 body-font overflow-hidden">
        <div class="container px-5 py-5 mx-auto ">
            <div
                class="max-w-sm w-full bg-gray-900 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all mx-auto flex-wrap justify-center">
                <div class=" bg-white flex justify-center">
                    <svg id="barcode"></svg>
                </div>
                <div class="p-3 space-y-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-200">{{ $data->omschrijving }}</h3>
                        <p class="text-gray-500 mt-1">{{ $data->ean }}</p>
                        @if($data->voorraad)
                            <p class="text-gray-500 mt-1">Totale voorraad: {{ $data->voorraad->totaal }}</p>
                            <p class="text-gray-500 mt-1">Vrij: {{ $data->voorraad->vrij }}</p>
                            <p class="text-gray-500 mt-1">Klantorder: {{ $data->voorraad->klantorder }}</p>
                        @endif

                    </div>

                    <div class="flex justify-between items-center">
                        <div class="space-y-1">
                            <p class="text-2xl font-bold text-gray-200">&euro;{{ $data->verkoopprijs }}</p>
                           {{-- <p class="text-2xl font-bold text-gray-200">Promoprijs</p>
                            <p class="text-sm text-gray-500 line-through">Originele prijs</p>--}}
                        </div>

                        <div class="flex items-center gap-1 text-gray-200">
                            {{ $data->leveranciers->naam }}
                        </div>
                    </div>


                </div>
                <div class="relative">
                    <img
                        src="https://placehold.co/400x300"
                        alt="{{ $data->omschrijving }}"
                        class="w-full h-52 object-cover"
                    />
                    {{--                <span class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                            Sale
                          </span>--}}
                </div>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            JsBarcode(".barcode").init();
            JsBarcode("#barcode", "{{ $data->ean }}", {
                format: "ean13",
                lineColor: 'black',
                width: 3,
                height: 80,
                displayValue: true,
                flat: true
            });
        });
    </script>
</x-app-layout>

