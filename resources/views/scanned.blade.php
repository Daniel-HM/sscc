<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            @if($type == 'ean')
                {{ __('EAN '.$barcode) }}
            @elseif($type == 'sscc')
                {{ __('SSCC '.$barcode) }}
            @endif
        </h2>
    </x-slot>

    @if($type == 'ean')
        <section class="dark:bg-gray-800 text-gray-600 body-font overflow-hidden">
            <div class="container px-5 py-24 mx-auto">
                <div class="lg:w-4/5 mx-auto flex flex-wrap justify-center">

                    <div class="lg:w-1/2 w-full lg:pl-10 lg:py-6 mt-6 lg:mt-0">

                        <h1 class="text-white text-3xl title-font font-small mb-1">{{ $data['omschrijving'] }}</h1>
                        <div class="flex mb-4">
          <span class="flex items-center">

            <span class="text-white ml-3">{{ $data['ean'] }}</span>
          </span>
                        </div>
                        <p class="leading-relaxed">{{ $data['leveranciers']['naam'] }}</p>

                    </div>
                </div>
            </div>

        </section>
    @elseif($type == 'sscc')
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <table class="table-auto text-sm w-full">
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
                                    <td>{{ $sscc->artikel->leveranciers->naam }}</td>
                                </tr>
                                @endforeach
                            </table>
                    </div>
                </div>
            </div>
        </div>

    @endif

</x-app-layout>

