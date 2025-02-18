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
                            <th class="p-1 text-right">Mail</th>
                            <th class="p-1 text-right">Telefoon</th>
                            <th class="p-1 text-right">Franco</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($leveranciers as $leverancier)

                            <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">
                                <td class="p-1">{{ $leverancier->naam }}</td>
                                <td class="p-1 text-right">{{ $leverancier->mail }}</td>
                                <td class="p-1 text-right">{{ $leverancier->telefoonnummer }}</td>
                                <td class="p-1 text-right">{{ $leverancier->franco }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

