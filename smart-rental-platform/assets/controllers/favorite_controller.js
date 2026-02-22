// Stimulus Controller for Favorite Toggle
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon'];

    connect() {
        this.isFavorite = false;
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        this.isFavorite = !this.isFavorite;
        this.updateIcon();

        // Here you would typically make an API call to save the favorite status
        // fetch('/api/favorites', { method: 'POST', body: JSON.stringify({ ... }) })
    }

    updateIcon() {
        if (this.isFavorite) {
            this.iconTarget.classList.remove('fill-black/30', 'text-white');
            this.iconTarget.classList.add('fill-red-500', 'text-red-500');
        } else {
            this.iconTarget.classList.remove('fill-red-500', 'text-red-500');
            this.iconTarget.classList.add('fill-black/30', 'text-white');
        }
    }
}
