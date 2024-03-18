<div class="lg:flex xs:block h-screen bg-background overflow-hidden  ">
    <div wire:loading wire:target="onSave">
        <x-loader />
    </div>
    <div id="login_form" class="lg:basis-3/5  bg-background my-auto flex flex-col  items-center">
        <div class="flex flex-col items-center">
            <img aria-hidden="true" class="w-24" src="{{ asset('img/logo.png') }}" alt="MG Image" />
            <p class="text-primary text-3xl font-black font-header mt-2 mb-10">Mary Grace Portal</p>
        </div>
        <div class="bg-white rounded-lg shadow-md h-auto md:w-6/12 border p-10">
            <h1 class="font-black text-3xl mb-10">Welcome Back!</h1>
            <form wire:submit.prevent="onSave">
                @csrf
                <label class="block text-sm">
                    <span class="text-slate-700 font-bold">Employee ID <span class="text-red-700">*</span></span>
                    <x-input class="appearance-none" wire:model="employeeId" :type="__('number')" :placeholder="__('0000')" />
                    @error('employeeId')
                        <x-input-error :messages="__($message)" />
                    @enderror
                </label>
                <label class="block mt-4 text-sm ">
                    <span class="text-slate-700 font-bold">Password <span class="text-red-700">*</span></span>
                    <x-input wire:model="password" :type="__('password')" :placeholder="__('***************')" />
                    @error('password')
                        <x-input-error :messages="__($message)" />
                    @enderror
                </label>
                <p class="mt-2 mb-5">
                    <a class="text-sm font-medium text-cta hover:underline" href="#">
                        Forgot your password?
                    </a>
                </p>
                <x-button class="bg-primary" wire:click="onSave" :name="__('Sign in')" />
            </form>
            <x-toast />
        </div>
    </div>
    <div class="md:hidden lg:block lg:basis-2/5  bg-primary">
        <div id="image" class="w-full h-screen bg-cover  opacity-50  "
            style="background-image: url('{{ asset('img/login_image.jpg') }}');"></div>
    </div>
</div>
