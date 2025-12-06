<x-layouts.app>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                {{ trans('global.project.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ trans('global.manage') }} {{ trans('global.project.title') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
                    data-view="card">
                    🗂️
                </button>
                <button type="button"
                    class="view-switch px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700"
                    data-view="list">
                    📋
                </button>
            </div>

            @can('project_create')
                <a href="{{ route('admin.project.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                      dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    {{ trans('global.add') }} {{ trans('global.new') }}
                </a>
            @endcan
        </div>
    </div>

    <div id="card-search" class="mb-4 hidden">
        <div class="flex flex-col md:flex-row gap-4">
            <select id="directorateFilter"
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <option value="">
                    {{ trans('global.allDirectorate') }}
                </option>
                @foreach ($directorates as $id => $title)
                    <option value="{{ $id }}">
                        {{ $title }}
                    </option>
                @endforeach
            </select>
            <select id="projectFilter"
                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-700 rounded-md
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                <option value="">
                    {{ trans('global.allProjects') }}
                </option>
            </select>
        </div>
    </div>

    <div id="card-view" class="mb-6 hidden">
        <div id="cardContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($data as $index => $item)
                <div class="card-item" data-search="{{ collect($item)->flatten()->implode(' ') }}"
                    data-directorate="{{ $item['directorate']['id'] }}" data-project-id="{{ $item['id'] }}">
                    <x-forms.project-card :title="$item['title']" :description="$item['description']" :directorate="$item['directorate']" :fields="$item['fields']"
                        :routePrefix="$routePrefix" :actions="$actions" :deleteConfirmationMessage="$deleteConfirmationMessage" :arrayColumnColor="$arrayColumnColor" :uniqueId="$index"
                        :id="$item['id']" :comment_count="$item['comment_count']" />
                </div>
            @endforeach
        </div>
    </div>

    <div id="list-view" class="mb-6">
        <x-table.dataTables.projects :headers="$tableHeaders" :data="$tableData" :routePrefix="$routePrefix" :actions="$actions"
            :deleteConfirmationMessage="$deleteConfirmationMessage" />
    </div>

    <div id="card-pagination" class="mt-4 flex justify-between items-center hidden">
        <div id="cardPaginationInfo" class="text-gray-600 dark:text-gray-300 text-sm md:text-base"></div>
        <div class="flex space-x-2" id="cardPaginationControls">
            <button id="cardPrevPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changeCardPage(-1)">Previous</button>
            <div id="cardPageNumbers" class="flex space-x-2">
            </div>
            <button id="cardNextPage"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                onclick="changeCardPage(1)">Next</button>
        </div>
    </div>

    <script>
        let cardCurrentPage = 1;
        const cardRowsPerPage = 12;
        const projectsData = @json($data); // Pass cardData to JavaScript

        // Function to populate project dropdown based on directorate
        function populateProjectDropdown(directorateId) {
            const projectFilter = document.getElementById('projectFilter');
            projectFilter.innerHTML = '<option value="">{{ trans('global.allProjects') }}</option>';

            const filteredProjects = directorateId ?
                projectsData.filter(project => project.directorate.id == directorateId) :
                projectsData;

            filteredProjects.forEach(project => {
                const option = document.createElement('option');
                option.value = project.id;
                option.textContent = project.title;
                projectFilter.appendChild(option);
            });
        }

        // Function to toggle views and their associated elements
        function toggleView(view) {
            const cardView = document.getElementById('card-view');
            const listView = document.getElementById('list-view');
            const cardSearch = document.getElementById('card-search');
            const cardPagination = document.getElementById('card-pagination');

            if (view === 'card') {
                cardView.classList.remove('hidden');
                listView.classList.add('hidden');
                cardSearch.classList.remove('hidden');
                cardPagination.classList.remove('hidden');
                updateCardView(); // Initialize card view pagination
            } else {
                cardView.classList.add('hidden');
                listView.classList.remove('hidden');
                cardSearch.classList.add('hidden');
                cardPagination.classList.add('hidden');
            }
        }

        // Update card view pagination
        function updateCardView() {
            const directorateFilter = document.getElementById('directorateFilter').value;
            const projectFilter = document.getElementById('projectFilter').value;
            const cards = Array.from(document.querySelectorAll('#cardContainer .card-item'));
            let filteredCards = cards.filter(card => {
                const directorateId = card.getAttribute('data-directorate');
                const projectId = card.getAttribute('data-project-id');
                return (directorateFilter === '' || directorateId === directorateFilter) &&
                    (projectFilter === '' || projectId === projectFilter);
            });

            const totalPages = Math.ceil(filteredCards.length / cardRowsPerPage);

            if (cardCurrentPage > totalPages && totalPages > 0) {
                cardCurrentPage = totalPages;
            } else if (totalPages === 0) {
                cardCurrentPage = 0;
            }

            const start = (cardCurrentPage - 1) * cardRowsPerPage;
            const end = start + cardRowsPerPage;
            const paginatedCards = filteredCards.slice(start, end);

            cards.forEach(card => card.style.display = 'none');
            paginatedCards.forEach(card => card.style.display = '');

            document.getElementById('cardPaginationInfo').textContent =
                `Page ${cardCurrentPage > 0 ? cardCurrentPage : 0} of ${totalPages} (Total: ${filteredCards.length} records)`;
            document.getElementById('cardPrevPage').disabled = cardCurrentPage <= 1;
            document.getElementById('cardNextPage').disabled = cardCurrentPage >= totalPages;

            renderCardPageNumbers(totalPages);
        }

        function changeCardPage(delta) {
            const directorateFilter = document.getElementById('directorateFilter').value;
            const projectFilter = document.getElementById('projectFilter').value;
            const cards = Array.from(document.querySelectorAll('#cardContainer .card-item'));
            const filteredCards = cards.filter(card => {
                const directorateId = card.getAttribute('data-directorate');
                const projectId = card.getAttribute('data-project-id');
                return (directorateFilter === '' || directorateId === directorateFilter) &&
                    (projectFilter === '' || projectId === projectFilter);
            });
            const totalPages = Math.ceil(filteredCards.length / cardRowsPerPage);

            cardCurrentPage += delta;
            if (cardCurrentPage < 1) cardCurrentPage = 1;
            if (cardCurrentPage > totalPages) cardCurrentPage = totalPages;
            updateCardView();
        }

        function goToCardPage(page) {
            cardCurrentPage = page;
            updateCardView();
        }

        function renderCardPageNumbers(totalPages) {
            const pageNumbersContainer = document.getElementById('cardPageNumbers');
            pageNumbersContainer.innerHTML = '';

            const maxPageButtons = 5;
            let startPage, endPage;

            if (totalPages <= maxPageButtons) {
                startPage = 1;
                endPage = totalPages;
            } else {
                const middle = Math.ceil(maxPageButtons / 2);
                if (cardCurrentPage <= middle) {
                    startPage = 1;
                    endPage = maxPageButtons;
                } else if (cardCurrentPage + middle > totalPages) {
                    startPage = totalPages - maxPageButtons + 1;
                    endPage = totalPages;
                } else {
                    startPage = cardCurrentPage - middle + 1;
                    endPage = cardCurrentPage + middle - 1;
                }
            }

            if (startPage > 1) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'px-3 py-1 text-gray-600 dark:text-gray-300';
                pageNumbersContainer.appendChild(ellipsis);
            }

            for (let i = startPage; i <= endPage; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className =
                    `px-3 py-1 rounded cursor-pointer ${cardCurrentPage === i ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'}`;
                button.onclick = () => goToCardPage(i);
                pageNumbersContainer.appendChild(button);
            }

            if (endPage < totalPages) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'px-3 py-1 text-gray-600 dark:text-gray-300';
                pageNumbersContainer.appendChild(ellipsis);
            }
        }

        // Attach event listeners for view switching
        document.querySelectorAll('.view-switch').forEach(button => {
            button.addEventListener('click', () => {
                const view = button.getAttribute('data-view');
                toggleView(view);
            });
        });

        // Attach event listener for directorate filter
        document.getElementById('directorateFilter').addEventListener('change', () => {
            cardCurrentPage = 1; // Reset to first page on filter change
            const directorateId = document.getElementById('directorateFilter').value;
            populateProjectDropdown(directorateId); // Populate project dropdown
            updateCardView();
        });

        // Attach event listener for project filter
        document.getElementById('projectFilter').addEventListener('change', () => {
            cardCurrentPage = 1; // Reset to first page on filter change
            updateCardView();
        });

        // Initialize with card view and populate project dropdown
        toggleView('card');
        populateProjectDropdown('');
    </script>
</x-layouts.app>
