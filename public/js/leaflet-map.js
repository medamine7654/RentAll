/**
 * Leaflet Map Integration for Covoiturage
 * Uses OpenStreetMap + Nominatim (no API key required)
 */

// Prevent redeclaration on Turbo navigation
if (typeof window.LeafletMapManager === 'undefined') {
    
class LeafletMapManager {
    constructor(mapId, options = {}) {
        this.mapId = mapId;
        this.map = null;
        this.markers = {
            depart: null,
            destination: null
        };
        this.polyline = null;
        this.clickMode = 'depart'; // 'depart' or 'destination'
        this.options = {
            defaultCenter: [48.8566, 2.3522], // Paris
            defaultZoom: 6,
            ...options
        };
        
        this.init();
    }

    init() {
        // Initialize map
        this.map = L.map(this.mapId).setView(this.options.defaultCenter, this.options.defaultZoom);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Add click handler
        this.map.on('click', (e) => this.handleMapClick(e));
    }

    async handleMapClick(e) {
        const { lat, lng } = e.latlng;
        
        try {
            // Reverse geocode to get address
            const address = await this.reverseGeocode(lat, lng);
            
            if (this.clickMode === 'depart') {
                this.setDepart(lat, lng, address);
            } else {
                this.setDestination(lat, lng, address);
            }
            
            // Auto-switch to destination after setting depart
            if (this.clickMode === 'depart' && this.options.autoSwitch !== false) {
                this.clickMode = 'destination';
                this.updateModeIndicator();
            }
            
            // Update polyline if both markers exist
            this.updatePolyline();
            
        } catch (error) {
            console.error('Geocoding error:', error);
            // Still set marker with coordinates even if geocoding fails
            const address = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            if (this.clickMode === 'depart') {
                this.setDepart(lat, lng, address);
            } else {
                this.setDestination(lat, lng, address);
            }
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

    setDepart(lat, lng, address) {
        // Remove old marker
        if (this.markers.depart) {
            this.map.removeLayer(this.markers.depart);
        }
        
        // Create green marker for depart
        const greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        this.markers.depart = L.marker([lat, lng], { icon: greenIcon })
            .addTo(this.map)
            .bindPopup(`<b>Départ</b><br>${address}`)
            .openPopup();
        
        // Update form field
        const departInput = document.getElementById('covoiturage_depart');
        if (departInput) {
            departInput.value = address;
        }
    }

    setDestination(lat, lng, address) {
        // Remove old marker
        if (this.markers.destination) {
            this.map.removeLayer(this.markers.destination);
        }
        
        // Create red marker for destination
        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        this.markers.destination = L.marker([lat, lng], { icon: redIcon })
            .addTo(this.map)
            .bindPopup(`<b>Destination</b><br>${address}`)
            .openPopup();
        
        // Update form field
        const destinationInput = document.getElementById('covoiturage_destination');
        if (destinationInput) {
            destinationInput.value = address;
        }
    }

    updatePolyline() {
        // Remove old polyline
        if (this.polyline) {
            this.map.removeLayer(this.polyline);
        }
        
        // Draw new polyline if both markers exist
        if (this.markers.depart && this.markers.destination) {
            const latlngs = [
                this.markers.depart.getLatLng(),
                this.markers.destination.getLatLng()
            ];
            
            this.polyline = L.polyline(latlngs, {
                color: '#6366f1',
                weight: 3,
                opacity: 0.7
            }).addTo(this.map);
            
            // Fit bounds to show both markers
            this.map.fitBounds(this.polyline.getBounds(), { padding: [50, 50] });
        }
    }

    async displayExistingLocations(departAddress, destinationAddress) {
        if (!departAddress || !destinationAddress) {
            console.warn('Missing addresses for map display');
            return;
        }
        
        try {
            // Geocode both addresses
            const departCoords = await this.geocode(departAddress);
            const destinationCoords = await this.geocode(destinationAddress);
            
            if (departCoords && destinationCoords) {
                this.setDepart(departCoords.lat, departCoords.lng, departAddress);
                this.setDestination(destinationCoords.lat, destinationCoords.lng, destinationAddress);
                
                // Update polyline
                this.updatePolyline();
            } else {
                console.warn('Could not geocode one or both addresses');
            }
        } catch (error) {
            console.error('Error displaying locations:', error);
        }
    }

    setClickMode(mode) {
        this.clickMode = mode;
        this.updateModeIndicator();
    }

    updateModeIndicator() {
        const indicator = document.getElementById('map-mode-indicator');
        if (indicator) {
            if (this.clickMode === 'depart') {
                indicator.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">🟢 Cliquez pour définir le DÉPART</span>';
            } else {
                indicator.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">🔴 Cliquez pour définir la DESTINATION</span>';
            }
        }
    }
}

// Export for use in templates
window.LeafletMapManager = LeafletMapManager;

}
