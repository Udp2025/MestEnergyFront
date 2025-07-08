<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <!-- Nombre -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        
        <!-- Imagen de perfil -->
        <div class="mt-4">
            <label for="profile_image">Imagen de perfil</label>
            <input id="profile_image" type="file" name="profile_image" accept="image/*">
            <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Selección de Rol -->
        <div class="form-group mt-4">
            <label for="role">Tipo de usuario:</label>
            <select name="role" id="role" class="form-control">
                <option value="normal">Normal</option>
                <option value="admin">Administrador</option>
            </select>
        </div>

        <!-- Selección de Cliente -->
        <div class="form-group mt-4">
            <label for="cliente_id">Cliente:</label>
            <select name="cliente_id" id="cliente_id" class="form-control">
                <!-- Opción para dejar sin cliente (para administradores) -->
                <option value="">Sin cliente</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}">
                        {{ $cliente->nombre }} - {{ $cliente->razon_social }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('cliente_id')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
