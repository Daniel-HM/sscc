<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('SSCC '.$barcode) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <table>
                        <tr>
                            <td>Aantal</td>
                            <td>Naam</td>
                            <td>EAN</td>
                            <td>Leverancier</td>
                        </tr>

                        @foreach ($data as $sscc)
                            <tr>
                                <td>{{ $sscc->aantal_ce }}</td>
                                <td>{{ $sscc->artikel->omschrijving }}</td>
                                <td>{{ $sscc->artikel->ean }}</td>
                                <td>{{ $sscc->artikel->leveranciers->naam }}
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

