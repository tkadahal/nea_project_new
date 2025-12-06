<aside
    class="js-sidebar bg-sidebar text-sidebar-foreground border-r border-gray-200 dark:border-gray-700 sidebar-transition overflow-hidden md:w-64 shadow-sm shadow-gray-300"
    data-open="false">
    <!-- Sidebar Content -->
    <div class="h-full flex flex-col">
        <!-- Sidebar Menu -->
        <nav class="flex-1 overflow-y-auto custom-scrollbar py-4">
            <ul class="space-y-1 px-2">
                <!-- Dashboard -->
                <x-layouts.sidebar-link href="{{ route('dashboard') }}" icon="fas-house" :active="request()->routeIs('dashboard*')">
                    {{ trans('global.dashboard') }}
                </x-layouts.sidebar-link>

                <li class="js-collapsible-menu">
                    <button
                        class="js-toggle-submenu flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span class="js-submenu-label transition-opacity duration-300 opacity-100">
                                {{ trans('global.analytics.title') }}
                            </span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="js-chevron h-4 w-4 transition-transform opacity-100" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div
                        class="js-submenu mt-1 ml-6 space-y-1 hidden border-l-2 border-gray-300 dark:border-gray-600 pl-2">
                        <a href="{{ route('admin.analytics.task') }}"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span>
                                    {{ trans('global.analytics.task.title') }}
                                </span>
                            </div>
                        </a>

                        <a href="{{ route('admin.analytics.project') }}"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span>
                                    {{ trans('global.analytics.project.title') }}
                                </span>
                            </div>
                        </a>
                    </div>
                </li>

                @can('admin_menu_access')
                    <!-- Admin Menu -->
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-2 font-semibold transition-opacity duration-300"
                                :class="{ 'opacity-0': !sidebarOpen }">
                                {{ trans('global.adminMenu.title') }}
                            </span>
                        </div>
                    </div>

                    @can('user_management_access')
                        <li class="js-collapsible-menu">
                            <button
                                class="js-toggle-submenu flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    <span class="js-submenu-label transition-opacity duration-300 opacity-100">
                                        {{ trans('global.userManagement.title') }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="js-chevron h-4 w-4 transition-transform opacity-100" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div
                                class="js-submenu mt-1 ml-6 space-y-1 hidden border-l-2 border-gray-300 dark:border-gray-600 pl-2">
                                @can('permission_access')
                                    <a href="{{ route('admin.permission.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>{{ trans('global.permission.title') }}</span>
                                        </div>
                                    </a>
                                @endcan

                                @can('role_access')
                                    <a href="{{ route('admin.role.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                            </svg>
                                            <span>{{ trans('global.role.title') }}</span>
                                        </div>
                                    </a>
                                @endcan

                                @can('user_access')
                                    <a href="{{ route('admin.user.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            <span>{{ trans('global.user.title') }}</span>
                                        </div>
                                    </a>
                                @endcan
                            </div>
                        </li>
                    @endcan

                    @can('setting_access')
                        <li class="js-collapsible-menu">
                            <button
                                class="js-toggle-submenu flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="js-submenu-label transition-opacity duration-300 opacity-100">
                                        {{ trans('global.settingManagement.title') }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="js-chevron h-4 w-4 transition-transform opacity-100" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div
                                class="js-submenu mt-1 ml-6 space-y-1 hidden border-l-2 border-gray-300 dark:border-gray-600 pl-2">

                                @can('status_access')
                                    <a href="{{ route('admin.status.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ trans('global.status.title') }}</span>
                                        </div>
                                    </a>
                                @endcan

                                @can('priority_access')
                                    <a href="{{ route('admin.priority.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 21v-7m0 0V7a2 2 0 012-2h3m10 9V7a2 2 0 00-2-2h-3m-5 16a2 2 0 002-2v-3a2 2 0 00-2-2H5a2 2 0 00-2 2v3a2 2 0 002 2h3z" />
                                            </svg>
                                            <span>{{ trans('global.priority.title') }}</span>
                                        </div>
                                    </a>
                                @endcan

                                @can('fiscalYear_access')
                                    <a href="{{ route('admin.fiscalYear.index') }}"
                                        class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 21v-7m0 0V7a2 2 0 012-2h3m10 9V7a2 2 0 00-2-2h-3m-5 16a2 2 0 002-2v-3a2 2 0 00-2-2H5a2 2 0 00-2 2v3a2 2 0 002 2h3z" />
                                            </svg>
                                            <span>{{ trans('global.fiscalYear.title') }}</span>
                                        </div>
                                    </a>
                                @endcan
                            </div>
                        </li>
                    @endcan

                    @can('directorate_access')
                        <x-layouts.sidebar-link href="{{ route('admin.directorate.index') }}" icon="fas-building"
                            :active="request()->routeIs('admin.directorate*')">
                            {{ trans('global.directorate.title') }}
                        </x-layouts.sidebar-link>
                    @endcan

                    @can('department_access')
                        <x-layouts.sidebar-link href="{{ route('admin.department.index') }}" icon="fas-bars"
                            :active="request()->routeIs('admin.department*')">
                            {{ trans('global.department.title') }}
                        </x-layouts.sidebar-link>
                    @endcan
                @endcan

                <li class="flex px-3 py-2 text-base font-bold text-blue-700">
                    {{ trans('global.menu.title') }}
                </li>

                <!-- Projects Dropdown -->
                @can('project_access')
                    <li class="js-collapsible-menu">
                        <button
                            class="js-toggle-submenu flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span class="js-submenu-label transition-opacity duration-300 opacity-100">
                                    {{ trans('global.project.title') }}
                                </span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="js-chevron h-4 w-4 transition-transform opacity-100" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <div
                            class="js-submenu mt-1 ml-6 space-y-1 hidden border-l-2 border-gray-300 dark:border-gray-600 pl-2">

                            @can('project_access')
                                <a href="{{ route('admin.project.index') }}"
                                    class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground {{ request()->routeIs('admin.project.*') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : '' }}">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        <span>
                                            {{ trans('global.project.title') }}
                                        </span>
                                    </div>
                                </a>
                            @endcan

                            @can('budget_access')
                                <a href="{{ route('admin.budget.index') }}"
                                    class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground {{ request()->routeIs('admin.budget*') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : '' }}">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>
                                            {{ trans('global.budget.title') }}
                                        </span>
                                    </div>
                                </a>
                            @endcan

                            @can('projectActivity_access')
                                <a href="{{ route('admin.projectActivity.index') }}"
                                    class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground {{ request()->routeIs('admin.projectActivity.*') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : '' }}">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        <span>
                                            {{ trans('global.projectActivity.title') }}
                                        </span>
                                    </div>
                                </a>
                            @endcan

                            @can('expense_access')
                                <a href="{{ route('admin.projectExpense.index') }}"
                                    class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground {{ request()->routeIs('admin.expense*') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : '' }}">
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span>
                                            {{ trans('global.expense.title') }}
                                        </span>
                                    </div>
                                </a>
                            @endcan

                        </div>
                    </li>
                @endcan

                @can('contract_access')
                    <x-layouts.sidebar-link href="{{ route('admin.contract.index') }}" icon="fas-list" :active="request()->routeIs('admin.contract*')">
                        {{ trans('global.contract.title') }}
                    </x-layouts.sidebar-link>
                @endcan

                @can('task_access')
                    <x-layouts.sidebar-link href="{{ route('admin.task.index') }}" icon="fas-pen-to-square"
                        :active="request()->routeIs('admin.task*')">
                        {{ trans('global.task.title') }}
                    </x-layouts.sidebar-link>
                @endcan

                @can('file_access')
                    <x-layouts.sidebar-link href="{{ route('admin.file.index') }}" icon="fas-folder" :active="request()->routeIs('admin.file*')">
                        {{ trans('global.file.title') }}
                    </x-layouts.sidebar-link>
                @endcan

                @can('event_access')
                    <x-layouts.sidebar-link href="{{ route('admin.event.index') }}" icon="fas-pen-to-square"
                        :active="request()->routeIs('admin.event*')">
                        {{ trans('global.event.title') }}
                    </x-layouts.sidebar-link>
                @endcan

                <li class="js-collapsible-menu">
                    <button
                        class="js-toggle-submenu flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span class="js-submenu-label transition-opacity duration-300 opacity-100">Reports</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="js-chevron h-4 w-4 transition-transform opacity-100" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div
                        class="js-submenu mt-1 ml-6 space-y-1 hidden border-l-2 border-gray-300 dark:border-gray-600 pl-2">
                        <a href="#"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profile</span>
                            </div>
                        </a>
                        <a href="login.html"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                <span>Login</span>
                            </div>
                        </a>
                        <a href="register.html"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>Register</span>
                            </div>
                        </a>
                        <a href="#"
                            class="block px-3 py-2 text-sm rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Error</span>
                            </div>
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</aside>
