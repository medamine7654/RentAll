// Stimulus Controller for Search Filters
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['searchInput', 'overlay', 'minPriceSlider', 'maxPriceSlider', 'minPriceDisplay', 'maxPriceDisplay'];

    handleSearch(event) {
        // Debounce search input
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            // Submit form or trigger search
            const form = this.element.closest('form');
            if (form) {
                form.submit();
            }
        }, 500);
    }

    clearSearch(event) {
        event.preventDefault();
        this.searchInputTarget.value = '';
        this.handleSearch();
    }

    toggleFilters(event) {
        event.preventDefault();
        this.overlayTarget.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeFilters(event) {
        event.preventDefault();
        this.overlayTarget.classList.add('hidden');
        document.body.style.overflow = '';
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    updatePriceDisplay() {
        if (this.hasMinPriceSliderTarget && this.hasMinPriceDisplayTarget) {
            this.minPriceDisplayTarget.textContent = `$${this.minPriceSliderTarget.value}`;
        }
        if (this.hasMaxPriceSliderTarget && this.hasMaxPriceDisplayTarget) {
            this.maxPriceDisplayTarget.textContent = `$${this.maxPriceSliderTarget.value}+`;
        }
    }

    disconnect() {
        clearTimeout(this.searchTimeout);
        document.body.style.overflow = '';
    }
}
