{{-- Staff panel brand: logo mark that suits the current theme, wordmark and area label. --}}
<span class="flex items-center gap-2.5">
    <img src="{{ asset(config('kattufy.brand.logo_on_light')) }}" alt="" class="h-9 w-9 object-contain dark:hidden">
    <img src="{{ asset(config('kattufy.brand.logo_on_dark')) }}" alt="" class="hidden h-9 w-9 object-contain dark:block">
    <span class="flex flex-col leading-tight">
        <span class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">Kattu<span style="color:#F2A900">fy</span> <span class="font-medium text-gray-500 dark:text-gray-400">Admin</span></span>
        <span class="text-[0.7rem] font-medium text-gray-500 dark:text-gray-400">{{ config('kattufy.brand.slogan') }}</span>
    </span>
</span>
