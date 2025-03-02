<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Pakbonnen
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                             role="alert">
                            @foreach ($errors->all() as $error)
                                <span class="block sm:inline">{{ $error }}</span>
                            @endforeach
                        </div>
                    @endif

                    <table class="table-auto text-base w-full">
                        <tr>
                            <th class="p-1 text-left">Naam</th>
                            <th class="p-1 text-right">Datum</th>
                        </tr>

                        @foreach ($pakbonnen as $pakbon)

                            <tr class="even:bg-gray-900/50 odd:bg-gray-900/80">

                                <td class="p-1 text-left"><a
                                        href="{{ route('pakbonnen.show', $pakbon->naam) }}">{{ $pakbon->naam }}</a>
                                </td>
                                <td class="p-1 text-right">{{ \Carbon\Carbon::parse($pakbon->pakbonDatum)->locale('nl')->isoFormat('dddd D MMMM') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>

