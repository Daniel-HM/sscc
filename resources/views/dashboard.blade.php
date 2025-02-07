<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @foreach ($pakbonnen as $pakbon)
                        <p>{{ $pakbon->naam }}</p>
                    @endforeach


                    @foreach ($sscc as $ssccNumber => $pallets)
                        <h2>{{ $ssccNumber }}</h2>
                        <ul>
                            @foreach ($pallets as $pallet)
                                <li>
                                    ID: {{ $pallet->id }},
                                    Artikel: {{ $pallet->artikel->omschrijving }}
                                    EAN: {{ $pallet->artikel->ean }}
                                    Aantal Collo: {{ $pallet->aantal_collo }},
                                    Aantal CE: {{ $pallet->aantal_ce }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
