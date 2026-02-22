// Stimulus Controller for Image Carousel
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['image', 'dots'];
    static values = {
        images: Array
    };

    connect() {
        this.currentIndex = 0;
    }

    next(event) {
        event.preventDefault();
        event.stopPropagation();

        this.currentIndex = (this.currentIndex + 1) % this.imagesValue.length;
        this.updateImage();
    }

    prev(event) {
        event.preventDefault();
        event.stopPropagation();

        this.currentIndex = (this.currentIndex - 1 + this.imagesValue.length) % this.imagesValue.length;
        this.updateImage();
    }

    updateImage() {
        // Update image source
        this.imageTarget.src = this.imagesValue[this.currentIndex];

        // Update dots
        if (this.hasDotsTarget) {
            const dots = this.dotsTarget.querySelectorAll('[data-index]');
            dots.forEach((dot, index) => {
                if (index === this.currentIndex) {
                    dot.classList.remove('bg-white/50');
                    dot.classList.add('bg-white');
                } else {
                    dot.classList.remove('bg-white');
                    dot.classList.add('bg-white/50');
                }
            });
        }
    }
}
