<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            @if($type == 'ean')
                {{ __('EAN '.$barcode) }}
            @elseif($type == 'sscc')
                {{ __('SSCC '.$barcode) }}
            @elseif($type == 'sps')
                {{ __('Pakbon '.$barcode) }}
            @endif
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">

                    @foreach($data as $leverancierNaam => $artikelGroep)
                        <div class="text-center font-semibold mt-4">{{ $leverancierNaam }}</div>
                        <hr>
                        <table class="table-auto text-sm w-full">
                            <thead>
                            <tr>
                                <th class="p-1 text-left">Naam</th>
                                <th class="p-1 text-right">Aantal</th>
                                <th class="p-1 text-right">Type</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($artikelGroep as $artikel)

                                <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">
                                    <td class="p-1"><a href="{{ route('artikel', $artikel->ean) }}">{{ $artikel->omschrijving }}<br/></a><a
                                            class="text-gray-500/80">{{ $artikel->ean }}</a></td>
                                    <td class="p-1 text-right">{{ $artikel->aantal_ce }}</td>
                                    <td class="p-1 text-right">{{ $artikel->ordertype }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-app-layout>

