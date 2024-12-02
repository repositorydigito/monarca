<!-- resources/views/filament/tables/expense-budget-matrix-empty.blade.php -->
<div class="flex items-center justify-center p-8">
    <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay datos para mostrar</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $message }}
        </p>
    </div>
</div>
