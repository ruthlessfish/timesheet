<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Company Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage your company information to display on invoices.') }}
        </p>
    </header>

    <div class="mt-6">
        @if($companies->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                No company information added yet. Add your company details to make your invoices more professional.
            </p>
        @else
            <div class="space-y-4 mb-6">
                @foreach($companies as $company)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $company->is_default ? 'bg-indigo-50 dark:bg-indigo-900/10 border-indigo-300 dark:border-indigo-700' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $company->name }}</h3>
                                @if($company->is_default)
                                    <span class="px-2 py-1 text-xs font-medium bg-indigo-600 text-white rounded">Default</span>
                                @endif
                            </div>
                            @if($company->address)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-line">{{ $company->address }}</p>
                            @endif
                            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                @if($company->phone)
                                    <span>📞 {{ $company->phone }}</span>
                                @endif
                                @if($company->email)
                                    <span>✉️ {{ $company->email }}</span>
                                @endif
                                @if($company->website)
                                    <span>🌐 {{ $company->website }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-4">
                            <a href="{{ route('profile.company.edit', $company) }}" 
                               class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                                Edit
                            </a>
                            @if(!$company->is_default)
                                <form method="POST" action="{{ route('profile.company.set-default', $company) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 text-sm font-medium">
                                        Set Default
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('profile.company.destroy', $company) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this company?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('profile.company.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            {{ $companies->isEmpty() ? 'Add Company' : 'Add Another Company' }}
        </a>
    </div>
</section>
