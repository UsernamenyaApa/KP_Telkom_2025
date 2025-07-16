<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Halaman ini hanya untuk tampilan, jadi tidak ada logika kompleks.
new #[Layout('components.layouts.minimal')] class extends Component
{
    //
}; ?>

{{-- Tampilan Halaman Sukses --}}
<div class="flex items-center justify-center w-full h-full p-4">

    <div class="w-full max-w-md p-8 space-y-1 text-center bg-gray-200/50 backdrop-blur-sm rounded-xl shadow-lg border border-gray-300/60">
        
        {{-- Ikon Centang Hijau --}}
        <div class="flex justify-center">
            <img src="/images/success password logo.png" alt="Success Logo" class="w-20 h-20">
        </div>
        
        {{-- Judul --}}
        <h2 class="text-2xl font-bold text-slate-900">
            Password berhasil diubah!
        </h2>

        {{-- Sub-judul --}}
        <p class="text-sm text-slate-700">
            Silahkan login kembali dengan password baru anda
        </p>

        {{-- Tombol Login --}}
        <div class="pt-6">
            <a href="{{ route('login') }}" wire:navigate
                class="inline-block px-8 py-2 text-sm font-medium text-white bg-slate-800 border border-transparent rounded-md shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-700 transition-colors">
                LOGIN
            </a>
        </div>
        
    </div>
</div>