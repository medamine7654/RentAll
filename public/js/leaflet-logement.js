/**
 * Leaflet Map Integration for Logement (Single Location)
 * Uses OpenStreetMap + Nominatim (no API key required)
 */

// Prevent redeclaration on Turbo navigation
if (typeof window.LeafletLogementMap === 'undefined') {
    
class LeafletLogementMap {
    constructor(mapId, options = {}) {
        this.mapId = mapId;
        this.map = null;
        this.marker = null;
        this.options = {
            defaultCenter: [48.8566, 2.3522], // Paris
            defaultZoom: 6,
            inputId: 'logement_adresse',
            ...options
        };
        
        this.init();
    }

    init() {
        this.map = L.map(this.mapId).setView(this.options.defaultCenter, this.options.defaultZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.map.on('click', (e) => this.handleMapClick(e));
    }

    async handleMapClick(e) {
        const { lat, lng } = e.latlng;
        
        try {
            const address = await this.reverseGeocode(lat, lng);
            this.setLocation(lat, lng, address);
        } catch (error) {
            console.error('Geocoding error:', error);
            const address = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            this.setLocation(lat, lng, address);
        }
    }

    async reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error('Geocoding failed');
        }
        
        const data = await response.json();
        return data.display_name || `${lat}, ${lng}`;
    }

    async geocode(address) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error('Geocoding failed');
        }
        
        const data = await response.json();
        if (data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
        
        return null;
    }

    setLocation(lat, lng, address) {
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }
        
        const blueIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        this.marker = L.marker([lat, lng], { icon: blueIcon })
            .addTo(this.map)
            .bindPopup(`<b>Emplacement du logement</b><br>${address}`)
            .openPopup();
        
        const addressInput = document.getElementById(this.options.inputId);
        if (addressInput) {
            addressInput.value = address;
            addressInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        this.map.setView([lat, lng], 15);
    }

    async displayExistingLocation(address) {
        if (!address) return;
        
        try {
            const coords = await this.geocode(address);
            if (coords) {
                this.setLocation(coords.lat, coords.lng, address);
            }
        } catch (error) {
            console.error('Error displaying location:', error);
        }
    }
}

window.LeafletLogementMap = LeafletLogementMap;

}
