import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'count', 'loading'];
    static values = {
        url: String,
        category: String,
        minPrice: String,
        maxPrice: String,
        guests: String
    };

    connect() {
        this.timeout = null;
        this.abortController = null;
    }

    search() {
        // Clear existing timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        // Debounce: wait 300ms after user stops typing
        this.timeout = setTimeout(() => {
            this.performSearch();
        }, 300);
    }

    async performSearch() {
        const query = this.inputTarget.value.trim();

        // Cancel previous request if still pending
        if (this.abortController) {
            this.abortController.abort();
        }

        // Show loading state
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('hidden');
        }

        // Build URL with all filters
        const params = new URLSearchParams();
        if (query) params.append('location', query);
        if (this.categoryValue) params.append('category', this.categoryValue);
        if (this.minPriceValue) params.append('minPrice', this.minPriceValue);
        if (this.maxPriceValue) params.append('maxPrice', this.maxPriceValue);
        if (this.guestsValue) params.append('guests', this.guestsValue);

        const url = `${this.urlValue}?${params.toString()}`;

        // Create new abort controller for this request
        this.abortController = new AbortController();

        try {
            const response = await fetch(url, {
                signal: this.abortController.signal
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            // Update results
            this.updateResults(data);

            // Update count
            if (this.hasCountTarget) {
                this.countTarget.textContent = data.count;
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                // Request was cancelled, ignore
                return;
            }
            console.error('Search error:', error);
        } finally {
            // Hide loading state
            if (this.hasLoadingTarget) {
                this.loadingTarget.classList.add('hidden');
            }
        }
    }

    updateResults(data) {
        if (!this.hasResultsTarget) return;

        if (data.count === 0) {
            this.resultsTarget.innerHTML = this.getEmptyState();
            return;
        }

        const html = data.results.map(logement => this.createLogementCard(logement)).join('');
        this.resultsTarget.innerHTML = html;
    }

    createLogementCard(logement) {
        const ratingHtml = logement.rating.total > 0 ? `
            <div class="flex items-center text-sm flex-shrink-0">
                <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
                <span class="font-bold text-gray-900">${logement.rating.average}</span>
            </div>
        ` : '';

        const imageHtml = logement.image ? `
            <img 
                src="${logement.image}" 
                alt="${logement.titre}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
            >
        ` : `
            <div class="w-full h-full bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 flex items-center justify-center">
                <svg class="w-20 h-20 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
        `;

        return `
            <div class="block group transform transition hover:-translate-y-1">
                <div class="overflow-hidden border-0 bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all">
                    <a href="${logement.url}">
                        <div class="relative aspect-square overflow-hidden">
                            ${imageHtml}
                            ${logement.type ? `
                                <div class="absolute left-3 top-3">
                                    <span class="inline-flex items-center bg-white/95 backdrop-blur-sm text-gray-900 px-3 py-1.5 rounded-lg text-xs font-bold shadow-md">
                                        ${logement.type}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="space-y-2 p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-gray-900 line-clamp-1 text-lg">
                                    ${logement.titre}
                                </h3>
                                ${ratingHtml}
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-1 flex items-center">
                                <svg class="w-4 h-4 inline mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                </svg>
                                ${logement.adresse}
                            </p>
                            <p class="text-sm text-gray-600 flex items-center gap-3">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    ${logement.nombreChambres} ch.
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    ${logement.capacite} pers.
                                </span>
                            </p>
                            <p class="pt-2 border-t border-gray-100">
                                <span class="font-bold text-gray-900 text-xl">${logement.prixParNuit}€</span>
                                <span class="text-gray-600 text-sm"> / nuit</span>
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        `;
    }

    getEmptyState() {
        return `
            <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-2xl shadow-lg col-span-full">
                <div class="rounded-full bg-gradient-to-br from-blue-100 to-blue-200 p-8 mb-6">
                    <svg class="h-16 w-16 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Aucun logement trouvé</h3>
                <p class="text-gray-600 mb-6 max-w-md">
                    Essayez d'ajuster vos critères de recherche pour trouver plus de résultats
                </p>
            </div>
        `;
    }

    // Update filter values when advanced filters change
    updateFilters(event) {
        const form = event.target.closest('form');
        const formData = new FormData(form);
        
        this.categoryValue = formData.get('category') || '';
        this.minPriceValue = formData.get('minPrice') || '';
        this.maxPriceValue = formData.get('maxPrice') || '';
        this.guestsValue = formData.get('guests') || '';
        
        // Trigger search with new filters
        this.performSearch();
    }
}
