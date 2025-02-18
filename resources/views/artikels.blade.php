<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">

                    <table class="table-auto text-sm w-full">
                        <thead>
                        <tr>
                            <th class="p-1 text-left">Naam</th>
                            <th class="p-1 text-right">Leverancier</th>
                            <th class="p-1 text-right">Categorie</th>
                            <th class="p-1 text-right">Prijs</th>
                            <th class="p-1 text-right">Voorraad</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($artikels as $artikel)

                            <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">
                                <td class="p-1"><a href="{{ route('artikel', $artikel->ean) }}">{{ $artikel->omschrijving }}</a><br/><a
                                        class="text-gray-500/80">{{ $artikel->ean }}</a></td>
                                   <td class="p-1 text-right">{{ $artikel->leveranciers->naam }}</td>
                                   <td class="p-1 text-right">{{ $artikel->kassagroep->omschrijving }}<br />{{ $artikel->assortimentsgroep->omschrijving }}</td>
                                   <td class="p-1 text-right">&euro;@php echo rand(1,10)@endphp,00</td>
                                   <td class="p-1 text-center">@php echo rand(1,10)@endphp</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    {{ $artikels->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

