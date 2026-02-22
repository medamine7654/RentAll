import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'suggestions'];
    static values = {
        url: String,
        suggestionsUrl: String
    };

    timeout = null;

    connect() {
        console.log('Dynamic search controller connected');
    }

    search(event) {
        clearTimeout(this.timeout);
        
        const query = this.inputTarget.value;
        
        if (query.length < 2) {
            this.clearResults();
            return;
        }

        // Debounce
        this.timeout = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }

    async performSearch(query) {
        try {
            const url = new URL(this.urlValue, window.location.origin);
            url.searchParams.set('q', query);

            // Ajouter les autres filtres depuis le formulaire
            const form = this.element.closest('form');
            if (form) {
                const formData = new FormData(form);
                for (const [key, value] of formData.entries()) {
                    if (value && key !== 'q') {
                        url.searchParams.set(key, value);
                    }
                }
            }

            const response = await fetch(url);
            const data = await response.json();

            this.displayResults(data.results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    async getSuggestions(event) {
        const query = this.inputTarget.value;
        
        if (query.length < 2) {
            this.clearSuggestions();
            return;
        }

        try {
            const url = new URL(this.suggestionsUrlValue, window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url);
            const data = await response.json();

            this.displaySuggestions(data.suggestions);
        } catch (error) {
            console.error('Suggestions error:', error);
        }
    }

    displayResults(results) {
        if (!this.hasResultsTarget) return;

        if (results.length === 0) {
            this.resultsTarget.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-gray-600">Aucun résultat trouvé</p>
                </div>
            `;
            return;
        }

        this.resultsTarget.innerHTML = results.map(logement => `
            <a href="${logement.url}" class="block group">
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden">
                    <div class="relative h-48 bg-gray-200">
                        ${logement.image ? 
                            `<img src="${logement.image}" class="w-full h-full object-cover" alt="${logement.titre}">` :
                            `<div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>`
                        }
                        ${logement.type ? 
                            `<div class="absolute left-2 top-2">
                                <span class="inline-flex items-center bg-white/90 backdrop-blur-sm text-gray-900 px-2 py-1 rounded-md text-xs font-semibold">
                                    ${logement.type}
                                </span>
                            </div>` : ''
                        }
                    </div>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-semibold text-gray-900">${logement.titre}</h3>
                            ${logement.rating.total > 0 ? 
                                `<div class="flex items-center text-sm flex-shrink-0">
                                    <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="font-semibold text-gray-900">${logement.rating.average}</span>
                                </div>` : ''
                            }
                        </div>
                        <p class="text-sm text-gray-500 mb-2">${logement.adresse}</p>
                        <p class="text-sm text-gray-500 mb-2">${logement.nombreChambres} ch. • ${logement.capacite} pers.</p>
                        <p>
                            <span class="font-semibold text-gray-900">${logement.prixParNuit}€</span>
                            <span class="text-gray-500"> / nuit</span>
                        </p>
                    </div>
                </div>
            </a>
        `).join('');
    }

    displaySuggestions(suggestions) {
        if (!this.hasSuggestionsTarget) return;

        if (suggestions.length === 0) {
            this.clearSuggestions();
            return;
        }

        this.suggestionsTarget.innerHTML = suggestions.map(suggestion => `
            <a href="/logement/${suggestion.id}" 
               class="block px-4 py-2 hover:bg-gray-100 transition">
                <div class="font-semibold text-gray-900">${suggestion.titre}</div>
                <div class="text-sm text-gray-600">${suggestion.adresse}</div>
            </a>
        `).join('');
        
        this.suggestionsTarget.classList.remove('hidden');
    }

    clearResults() {
        if (this.hasResultsTarget) {
            this.resultsTarget.innerHTML = '';
        }
    }

    clearSuggestions() {
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.innerHTML = '';
            this.suggestionsTarget.classList.add('hidden');
        }
    }

    hideSuggestions() {
        setTimeout(() => {
            this.clearSuggestions();
        }, 200);
    }
}
