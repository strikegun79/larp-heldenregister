<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Task bearbeiten: {{ $definition->label }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('admin.task-manager._form')
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.task-manager.index') }}" class="text-stone-500 hover:text-waldritter text-sm">
                    &larr; Zurück zur Übersicht
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
