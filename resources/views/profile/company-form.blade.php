<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($company) ? __('Edit Company') : __('Add Company') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST"
                        action="{{ isset($company) ? route('profile.company.update', $company) : route('profile.company.store') }}"
                        class="space-y-6">
                        @csrf
                        @if(isset($company))
                        @method('PATCH')
                        @endif

                        <!-- Company Name -->
                        <div>
                            <x-input-label for="name" :value="__('Company Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $company->name ?? '')" required autocomplete="organization" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Your business or freelance company name
                            </p>
                        </div>

                        <!-- Company Address -->
                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <textarea id="address" name="address" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address', $company->address ?? '') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Full address including street, city, state/province, and postal code
                            </p>
                        </div>

                        <!-- Company Phone -->
                        <div>
                            <x-input-label for="phone" :value="__('Phone Number')" />
                            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                                :value="old('phone', $company->phone ?? '')" autocomplete="tel" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <!-- Company Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                :value="old('email', $company->email ?? '')" autocomplete="email" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leave empty to use your account email on invoices
                            </p>
                        </div>

                        <!-- Company Website -->
                        <div>
                            <x-input-label for="website" :value="__('Website')" />
                            <x-text-input id="website" name="website" type="url" class="mt-1 block w-full"
                                :value="old('website', $company->website ?? '')" placeholder="https://example.com"
                                autocomplete="url" />
                            <x-input-error class="mt-2" :messages="$errors->get('website')" />
                        </div>

                        <!-- Set as Default -->
                        @if(!isset($company) || !$company->is_default)
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_default" value="1" {{ old('is_default', !isset($company)
                                    && $companies->isEmpty()) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm
                                focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Set as default company (used
                                    on new invoices)</span>
                            </label>
                        </div>
                        @endif

                        <div
                            class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('profile.edit') }}#company"
                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
                                Cancel
                            </a>
                            <x-primary-button type="submit">
                                {{ isset($company) ? __('Update Company') : __('Add Company') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>