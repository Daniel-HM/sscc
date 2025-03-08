<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">
                    @if($artikels->isNotEmpty())
                        <table class="table-auto text-sm w-full">
                            <thead>
                            <tr>
                                <th class="p-1 text-left">
                                    <a href="{{ route('artikels', ['omschrijving', ($currentOrderBy == 'artikels.omschrijving' && $currentDirection == 'asc') ? 'desc' : 'asc']) }}">
                                        Naam
                                        @if($currentOrderBy == 'artikels.omschrijving')
                                            @if($currentDirection == 'asc')
                                                ↑
                                            @else
                                                ↓
                                            @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="p-1 text-left">
                                    <a href="{{ route('artikels', ['leverancier', ($currentOrderBy == 'leveranciers.naam' && $currentDirection == 'asc') ? 'desc' : 'asc']) }}">
                                        Leverancier
                                        @if($currentOrderBy == 'leveranciers.naam')
                                            @if($currentDirection == 'asc')
                                                ↑
                                            @else
                                                ↓
                                            @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="p-1 text-right">
                                    <a href="{{ route('artikels', ['categorie', ($currentOrderBy == 'assortimentsgroep.omschrijving' && $currentDirection == 'asc') ? 'desc' : 'asc']) }}">
                                        Categorie
                                        @if($currentOrderBy == 'assortimentsgroep.omschrijving')
                                            @if($currentDirection == 'asc')
                                                ↑
                                            @else
                                                ↓
                                            @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="p-1 text-right">
                                    <a href="{{ route('artikels', ['prijs', ($currentOrderBy == 'artikels.verkoopprijs' && $currentDirection == 'asc') ? 'desc' : 'asc']) }}">
                                        Prijs
                                        @if($currentOrderBy == 'artikels.verkoopprijs')
                                            @if($currentDirection == 'asc')
                                                ↑
                                            @else
                                                ↓
                                            @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="p-1 text-right">
                                    <a href="{{ route('artikels', ['voorraad', ($currentOrderBy == 'voorraad.vrij' && $currentDirection == 'desc') ? 'asc' : 'desc']) }}">
                                        Voorraad
                                        @if($currentOrderBy == 'voorraad.vrij')
                                            @if($currentDirection == 'desc')
                                                ↑
                                            @else
                                                ↓
                                            @endif
                                        @endif
                                    </a>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($artikels as $artikel)

                                <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">
                                    <td class="p-1"><a
                                            href="{{ route('artikel', $artikel->ean) }}">{{ $artikel->omschrijving }}</a><br/><a
                                            class="text-gray-500/80">{{ $artikel->ean }}</a></td>
                                    <td class="p-1 text-left">{{ $artikel->leveranciers->naam }}</td>
                                    <td class="p-1 text-right">@if($artikel->kassagroep)
                                            {{ $artikel->kassagroep->omschrijving }}<br/>
                                        @endif{{ $artikel->assortimentsgroep->omschrijving }}</td>
                                    <td class="p-1 text-right">&euro;{{ $artikel->verkoopprijs }}</td>
                                    <td class="p-1 text-center">@if($artikel->voorraad)
                                            {{ $artikel->voorraad->vrij }}
                                        @else
                                            0
                                        @endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {{ $artikels->links() }}
                    @else
                        <p>Geen artikels gevonden.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

