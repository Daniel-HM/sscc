<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">
@if($leveranciers->isNotEmpty())
                    <table class="table-auto text-sm w-full">
                        <thead>
                        <tr>
                            <th class="p-2 text-left">Naam</th>
                            <th class="p-2 text-left">Info</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($leveranciers as $leverancier)

                            <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">
                                <td class="p-2">{{ $leverancier->naam }}<br/>
                                    @if($leverancier->adres_land)
                                        <a class="text-gray-500/80">{{ $leverancier->adres_land }}</a>
                                    @endif
                                </td>
                                @if($leverancier->email || $leverancier->telefoon || $leverancier->franco)

                                    <td class="p-2 text-left">
                                        @if($leverancier->email)
                                            {{ $leverancier->email }}<br/>
                                        @endif
                                        @if($leverancier->telefoon)
                                            Tel.: {{ $leverancier->telefoon }}<br/>
                                        @endif
                                        @if($leverancier->franco)
                                            Franco: &euro;{{ $leverancier->franco }}
                                        @endif
                                    </td>
                                @else
                                    <td class="p-2 text-left">
                                        <a class="text-gray-500/80">Geen info</a>
                                    </td>
                                @endif

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    {{ $leveranciers->links() }}
                    @else
                    <p>Geen leveranciers gevonden.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

