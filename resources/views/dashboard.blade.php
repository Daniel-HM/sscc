<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="dark:bg-gray-800 py-12 sm:py-32 mx-auto">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 items-center">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-4">
                    <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                        <dt class="text-base/7 text-gray-200">Pakbonnen verwerkt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-stone-200 sm:text-5xl">{{ number_format($pakbonnen, 0, ',', '.') }}</dd>
                    </div>
                    <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                        <dt class="text-base/7 text-gray-200">SSCC labels verwerkt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-stone-200 sm:text-5xl">{{ number_format($sscc->count(), 0, ',', '.') }}</dd>
                    </div>
                    <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                        <dt class="text-base/7 text-gray-200">Artikels in database</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-stone-200 sm:text-5xl">{{ number_format($artikels, 0, ',', '.') }}</dd>
                    </div>
                    <div class="mx-auto flex max-w-xs flex-col gap-y-4 mb-12">
                        <dt class="text-base/7 text-gray-200">Leveranciers in database</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-stone-200 sm:text-5xl">{{ number_format($leveranciers, 0, ',', '.') }}</dd>
                    </div>
                </dl>
                <div class="mx-auto flex items-center justify-center"><h3 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Top 20 artikels per assortimentsgroep</h3></div>
                <div style="width:90%;" class="mx-auto flex mb-12">
                    <x-chartjs-component :chart="$assortimentChart" />
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
