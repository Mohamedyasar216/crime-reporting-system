/* map-picker.js */
document.addEventListener('DOMContentLoaded', function () {
    // Check if map container exists
    const mapContainer = document.getElementById('map-picker');
    if (!mapContainer) return;

    // Default View (India Center)
    const defaultLat = 20.5937;
    const defaultLng = 78.9629;

    const map = L.map('map-picker').setView([defaultLat, defaultLng], 5);
    // Expose map to global scope
    window.reportMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let marker;

    let currentDistrictGeoJSON;
    let districtBoundary;

    // ---------------------------------------------------------
    // Function 1: Update Marker & Inputs
    // ---------------------------------------------------------
    function updateLocation(lat, lng, fetchDetails = true) {
        const districtSelect = document.getElementById('district_selector');
        const selectedDistrictName = districtSelect ? districtSelect.options[districtSelect.selectedIndex].text : '';

        // Check if district is selected first
        if (!selectedDistrictName || selectedDistrictName === 'Select District') {
            alert("Please select a District first before marking the location on the map.");
            return;
        }

        // --- STRICT BOUNDARY VALIDATION ---
        if (currentDistrictGeoJSON) {
            const point = turf.point([lng, lat]);
            const isInside = turf.booleanPointInPolygon(point, currentDistrictGeoJSON);

            if (!isInside) {
                alert(`Pinning allowed only INSIDE the boundary of ${selectedDistrictName}. Please pin correctly.`);
                return;
            }
        }

        if (fetchDetails) {
            // Use local proxy to avoid CORS/403 issues
            fetch(`proxy_geo.php?lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    proceedUpdate(lat, lng, data);
                })
                .catch(err => {
                    console.error("Geocoding failed", err);
                    proceedUpdate(lat, lng, null);
                });
        } else {
            proceedUpdate(lat, lng, null);
        }
    }

    function proceedUpdate(lat, lng, data) {
        // Update Marker
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }

        // Update Hidden Inputs
        document.getElementById('lat-input').value = lat;
        document.getElementById('lng-input').value = lng;

        // Update Coord Display
        const disp = document.getElementById('coord-display');
        if (disp) disp.innerText = lat.toFixed(6) + ", " + lng.toFixed(6);

        if (data && data.display_name) {
            const addressField = document.querySelector('textarea[name="landmark"]');
            if (addressField) addressField.value = data.display_name;

            const areaField = document.querySelector('input[name="area"]');
            if (areaField && !areaField.value && (data.address.suburb || data.address.neighbourhood)) {
                areaField.value = data.address.suburb || data.address.neighbourhood;
            }
        }
    }

    // ---------------------------------------------------------
    // Event: Map Click 
    // ---------------------------------------------------------
    map.on('click', function (e) {
        updateLocation(e.latlng.lat, e.latlng.lng, true);
    });

    // ---------------------------------------------------------
    // District Zoom Logic
    // ---------------------------------------------------------
    const districtNamingOverrides = {
        "Tiruvallur": "Thiruvallur",
        "Tiruvarur": "Thiruvarur",
        "Tirupathur": "Tirupattur",
        "Kanyakumari": "Kanniyakumari",
        "Thoothukudi": "Tuticorin"
    };

    const districtSelector = document.getElementById('district_selector');
    if (districtSelector) {
        districtSelector.addEventListener('change', function () {
            let cityName = this.options[this.selectedIndex].text;
            if (cityName && cityName !== 'Select District') {
                const coordDisplay = document.getElementById('coord-display');
                if (coordDisplay) coordDisplay.innerText = "Loading boundary for " + cityName + "...";

                // Check for naming overrides
                const searchTerm = districtNamingOverrides[cityName] || cityName;

                // Use a more specific query to prioritize the District boundary
                const query = `${searchTerm} District, Tamil Nadu, India`;

                // Use structured parameters if possible, but 'q' with 'District' is usually very reliable
                fetch(`proxy_geo.php?q=${encodeURIComponent(query)}&polygon_geojson=1&limit=1&countrycodes=in`)
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const result = data[0];
                            const lat = parseFloat(result.lat);
                            const lon = parseFloat(result.lon);

                            // Store Geometry for Validation (Support GeometryCollections)
                            let geojson = result.geojson;
                            if (geojson.type === 'GeometryCollection') {
                                const polygons = geojson.geometries.filter(g => g.type === 'Polygon' || g.type === 'MultiPolygon');
                                if (polygons.length > 0) geojson = polygons[0];
                            }
                            currentDistrictGeoJSON = geojson;

                            // Clear existing boundary
                            if (districtBoundary) {
                                map.removeLayer(districtBoundary);
                            }

                            // Draw new boundary (Bolder for mobile visibility)
                            if (result.geojson) {
                                districtBoundary = L.geoJSON(result.geojson, {
                                    interactive: false,
                                    style: {
                                        color: '#2563eb',
                                        weight: 5,
                                        fillColor: '#2563eb',
                                        fillOpacity: 0.15,
                                        dashArray: '5, 10'
                                    }
                                }).addTo(map);
                            }

                            if (result.boundingbox) {
                                const bbox = result.boundingbox;
                                const isMobile = window.innerWidth < 768;
                                const fitPadding = isMobile ? [20, 20] : [40, 40];

                                map.fitBounds([
                                    [bbox[0], bbox[2]],
                                    [bbox[1], bbox[3]]
                                ], {
                                    padding: fitPadding,
                                    animate: true,
                                    duration: 1.5
                                });
                            } else {
                                const isMobile = window.innerWidth < 768;
                                map.setView([lat, lon], isMobile ? 11 : 12);
                            }

                            // Force Marker Reset on District Change
                            if (marker) {
                                map.removeLayer(marker);
                                marker = null;
                                document.getElementById('lat-input').value = '';
                                document.getElementById('lng-input').value = '';
                                document.getElementById('coord-display').innerText = "Not selected (Inside Boundary Only)";
                            }

                            // Refresh map size again after a short delay for mobile
                            setTimeout(() => map.invalidateSize(), 500);
                        } else {
                            // Fallback 1: Try without "District" in the query
                            fetch(`proxy_geo.php?q=${encodeURIComponent(searchTerm + ', Tamil Nadu, India')}&polygon_geojson=1&limit=1&countrycodes=in`)
                                .then(r => r.json())
                                .then(data2 => {
                                    if (data2 && data2.length > 0) {
                                        const result2 = data2[0];
                                        let gj2 = result2.geojson;
                                        if (gj2.type === 'GeometryCollection') {
                                            const p2 = gj2.geometries.filter(g => g.type === 'Polygon' || g.type === 'MultiPolygon');
                                            if (p2.length > 0) gj2 = p2[0];
                                        }
                                        currentDistrictGeoJSON = gj2;

                                        if (result2.boundingbox) {
                                            const bbox2 = result2.boundingbox;
                                            map.fitBounds([[bbox2[0], bbox2[2]], [bbox2[1], bbox2[3]]], { padding: [20, 20] });
                                        } else {
                                            map.setView([result2.lat, result2.lon], 11);
                                        }
                                    } else {
                                        // Final Fallback: Search without the override if override was used
                                        if (searchTerm !== cityName) {
                                            fetch(`proxy_geo.php?q=${encodeURIComponent(cityName + ' District, Tamil Nadu, India')}&polygon_geojson=1&limit=1&countrycodes=in`)
                                                .then(r => r.json())
                                                .then(data3 => {
                                                    if (data3 && data3.length > 0) {
                                                        const result3 = data3[0];
                                                        currentDistrictGeoJSON = result3.geojson;
                                                        if (result3.boundingbox) {
                                                            const bbox3 = result3.boundingbox;
                                                            map.fitBounds([[bbox3[0], bbox3[2]], [bbox3[1], bbox3[3]]], { padding: [30, 30] });
                                                        } else {
                                                            map.setView([result3.lat, result3.lon], 11);
                                                        }
                                                    }
                                                });
                                        }
                                    }
                                });
                        }
                    })
                    .catch(err => {
                        console.error("District search failed:", err);
                        if (coordDisplay) coordDisplay.innerText = "Error loading district boundary";
                    });
            } else {
                // If Select District is chosen (empty)
                currentDistrictGeoJSON = null;
                if (districtBoundary) map.removeLayer(districtBoundary);
            }
        });
    }

    // Handle Window Resize for Map (Robust)
    window.addEventListener('resize', throttledInvalidate);
    function throttledInvalidate() {
        if (window.reportMap) {
            window.reportMap.invalidateSize();
            // Re-center on boundary if active
            if (districtBoundary) {
                window.reportMap.fitBounds(districtBoundary.getBounds(), { padding: [20, 20] });
            }
        }
    }

    // Initial Geolocation
    if (navigator.geolocation && !districtSelector.value) {
        navigator.geolocation.getCurrentPosition(position => {
            if (!document.getElementById('lat-input').value && !districtSelector.value) {
                map.setView([position.coords.latitude, position.coords.longitude], 11);
            }
        });
    }

    // Secondary size check for mobile
    setTimeout(() => {
        if (window.reportMap) window.reportMap.invalidateSize();
    }, 2000);
});
