console.log('directorate-projects.js loaded'); // Debug log

document.addEventListener('alpine:init', () => {
    console.log('Registering directorateWatcher'); // Debug log
    Alpine.data('directorateWatcher', () => ({
        selectedDirectorate: null,
        init() {
            const preSelected = document.querySelector('select[name="directorate_id"]')?.value || null;
            this.selectedDirectorate = preSelected;

            this.$watch('selectedDirectorate', (newValue) => {
                if (newValue) {
                    this.fetchProjects(newValue);
                } else {
                    this.$dispatch('projects-updated', { options: [], selected: [] });
                }
            });
        },
        fetchProjects(directorateId) {
            fetch(`/api/projects/by-directorate/${directorateId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch projects');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const options = Object.entries(data.data).map(([value, label]) => ({ value, label }));
                        this.$dispatch('projects-updated', { options, selected: [] });
                    } else {
                        throw new Error('API returned unsuccessful response');
                    }
                })
                .catch(error => {
                    console.error('Error fetching projects:', error);
                    this.$dispatch('fetch-error', { message: 'Failed to load projects. Please try again.' });
                });
        },
    }));

    console.log('Registering projectsUpdater'); // Debug log
    Alpine.data('projectsUpdater', (initialOptions = [], initialSelected = []) => ({
        options: initialOptions,
        selectedOptions: initialSelected,
        init() {
            this.options = initialOptions;
            this.selectedOptions = initialSelected;
        },
    }));
});