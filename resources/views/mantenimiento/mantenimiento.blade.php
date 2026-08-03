@extends('layouts.master-no-nav')

@section('title', 'Mantenimiento')

@section('content')
<div class="min-h-screen bg-slate-900 flex items-center justify-center px-4 py-10" style="padding-top:0;">

    <div class="w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50">

        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950/80 px-6 py-4">

            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                <span class="h-3 w-3 rounded-full bg-green-500"></span>
            </div>

            <span class="font-semibold text-slate-300 tracking-wide">Gestión de Gastos</span>

            <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">EN DESARROLLO</span>

        </div>

        <div class="grid lg:grid-cols-2">

            <div class="flex items-center justify-center bg-gradient-to-br from-slate-900 to-slate-800 p-10">
                <img
                    src="https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExb3c5eWJzczlyMTdhNGhtbWhvdXYycHc1MnY2NmZxN3lua3Ixa2FuMyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/Sm9AfJRiZofjlrkAAl/giphy.gif"
                    alt="Página en mantenimiento"
                    class="rounded-3xl border border-slate-700 shadow-2xl transition duration-500 hover:scale-[1.02]">
            </div>

            <div class="flex flex-col justify-center p-10">

                <span class="mb-6 w-max rounded-full border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-300">
                    🚧 Próximamente disponible
                </span>

                <h1 class="text-5xl font-black leading-tight text-white">
                    Estamos construyendo <span class="bg-gradient-to-r from-cyan-400 via-indigo-400 to-cyan-400 bg-clip-text text-transparent">algo increíble.</span>
                </h1>

                <p class="mt-8 text-lg leading-8 text-slate-400">
                    Este módulo aún está siendo desarrollado para ofrecer una experiencia rápida, intuitiva y segura.
                    Queremos entregarte una herramienta completa, por lo que algunas funciones todavía no están disponibles.
                </p>

                <div class="mt-10 rounded-2xl border border-slate-700 bg-slate-800/60 p-6">

                    <div class="flex items-center">

                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-900 text-2xl font-bold text-white">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-cyan-500">A</span>
                        </div>

                        <div class="ml-5">
                            <h3 class="text-xl font-bold text-white">Adri</h3>
                            <p class="text-slate-400">Desarrollador Full Stack</p>
                        </div>

                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4">

                        <div class="rounded-xl border border-transparent bg-slate-900 p-4 transition hover:border-cyan-500/30">
                            <p class="text-sm text-slate-400">Horario</p>
                            <p class="mt-1 font-semibold text-white">15:00 - 21:00</p>
                        </div>

                        <div class="rounded-xl border border-transparent bg-slate-900 p-4 transition hover:border-cyan-500/30">
                            <p class="text-sm text-slate-400">Estado</p>
                            <p class="mt-1 flex items-center gap-2 font-semibold text-emerald-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Trabajando
                            </p>
                        </div>

                    </div>

                </div>

                <a href="https://wa.me/59179888519?text=Hola%20Adri%2C%20estoy%20en%20la%20p%C3%A1gina%20de%20gastos.%20avisame%20cuando%20est%C3%A9%20lista%20👋"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="mt-10 flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-8 py-5 text-lg font-bold text-white transition duration-300 hover:bg-emerald-600 hover:shadow-lg hover:shadow-emerald-500/25">
                    <i class="fab fa-whatsapp text-lg"></i>
                    Contactar por WhatsApp
                </a>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Gracias por tu paciencia ❤️
                </p>

            </div>

        </div>

    </div>

</div>
@endsection