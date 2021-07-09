<template>
    <div class="bounds-picker-container">
        <label>{{ t('bounds_picker.title') }}</label>

        <div class="input-group">
            <span class="input-group-btn">
                <select v-model="queryType" class="form-control" data-sonata-select2="false" style="width:auto">
                    <option value="address">{{ t('bounds_picker.search_by_address') }}</option>
                    <option value="bounds">{{ t('bounds_picker.draw_rectangle') }}</option>
                </select>
            </span>
            <input @keypress.enter.prevent="geocodeAddress" class="form-control" v-model="inputAddress" :disabled="queryType != 'address'" 
                   :placeholder="t('bounds_picker.search_placeholder')" ref="inputAddress"/>
            <span class="input-group-btn">
                <button type="button" @click="geocodeAddress" class="btn btn-primary"
                       :disabled="queryType != 'address'">
                    {{ t('bounds_picker.search_button') }}
                </button>
            </span>
        </div>
        <div class="alert alert-danger" v-show="geocodeErrorMsg">{{ geocodeErrorMsg }}</div>

        <div class="bounds-picker-map" ref="map"></div>
    </div>
</template>

<script>
import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-shades'
import 'leaflet-shades/src/css/leaflet-shades.css'

export default {
    props: [ 'osmQueryObject', 'tileLayer', 'defaultBounds' ],
    data() {
        return {
            queryType: null, // either 'address' or 'bounds'
            inputAddress: undefined,
            geocodedAddress: {},
            geocodeErrorMsg: '',
            mapBounds: null,
            drawnBounds: null, // drawn bounds by user with leaflet-shades
            map: undefined,
            currentLayers: undefined,
            mapShades: undefined,
        }
    },
    computed: {
        bounds() {
            // if (this.addressPresent) return null
            return this.queryType == 'bounds' && this.drawnBounds ? this.drawnBounds : this.mapBounds
        },
        address() {
            if (this.queryType == 'address' && this.geocodedAddress && this.geocodedAddress.osm_id)
                return this.inputAddress
            else
                return null
        },
        // builds the geographical part of the overpass query
        overpassQuery() {
            if (this.address) {
                var areaRef = 1 * this.geocodedAddress.osm_id;
                if (this.geocodedAddress.osm_type == "way") areaRef += 2400000000;
                if (this.geocodedAddress.osm_type == "relation") areaRef += 3600000000;
                return `(area:${areaRef})`
            } else {
                let b = this.bounds
                return `(${b.getSouth()},${b.getWest()},${b.getNorth()},${b.getEast()})`
            }
        }
    },
    mounted() {
        // Init map
        this.map = L.map(this.$refs.map, {editable: true});
        L.tileLayer(this.tileLayer).addTo(this.map);
        this.map.on('moveend', () => this.mapBounds = this.map.getBounds())
        this.currentLayers = new L.layerGroup()
        this.currentLayers.addTo(this.map)

        // Initialise state from saved osmQueryObject
        let initialBounds = this.defaultBounds
        if (this.osmQueryObject && this.osmQueryObject.address) {
            this.inputAddress = this.osmQueryObject.address
            this.geocodeAddress()
            this.queryType = 'address'
        } else if (this.osmQueryObject && this.osmQueryObject.bounds) {
            initialBounds = new L.latLngBounds(this.osmQueryObject.bounds)
            this.drawnBounds = initialBounds
            this.queryType = 'bounds'
        }   

        // Init map position
        this.map.fitBounds(initialBounds);
        this.mapBounds = this.map.getBounds()
    },
    watch: {
        // When switching query type we need to turn on/off leaflet shades plugin
        queryType() {
            if (this.mapShades) {
                this.mapShades.onRemove(this.map) // See https://github.com/mkong0216/leaflet-shades/issues/3
                this.mapShades = null
            }
            this.currentLayers.clearLayers()
            if (this.queryType == 'bounds') {
                if (this.drawnBounds) {
                    const rect = L.rectangle(this.drawnBounds);
                    this.currentLayers.addLayer(rect)
                    rect.enableEdit();
                } else {
                    this.currentLayers.addLayer(this.map.editTools.startRectangle());
                }
                
                // Init shades
                this.mapShades = new L.LeafletShades();
                this.mapShades.addTo(this.map);
                this.mapShades.on('shades:bounds-changed', (event) => {
                    this.drawnBounds = event.bounds
                });
            }
        }
    },
    methods: {
        geocodeAddress() {
            if (!this.inputAddress) {
                this.geocodeErrorMsg = "Veuillez entrer une adresse"
                return
            }
            let url = `https://nominatim.openstreetmap.org/search.php?q=${this.inputAddress}&polygon_geojson=1&format=jsonv2`
            $.getJSON(url, results => {
                if (!results || results.length == 0) {
                    this.geocodeErrorMsg = this.t('bounds_picker.nomatch', { address: this.inputAddress});
                    return
                }
                this.geocodeErrorMsg = ''
                this.currentLayers.clearLayers()
                this.geocodedAddress = results[0]
                let layer = L.geoJSON(this.geocodedAddress.geojson, {
                    style: feature => {color: 'blue'}
                })
                this.currentLayers.addLayer(layer)
                this.map.fitBounds(layer.getBounds())
            })
        }
    }
}
</script>

<style lang="scss">
    .bounds-picker-container {
        margin-top: 1.5rem;
        .alert-danger {
            margin: 1.5rem 0;
        }
        .bounds-picker-map {
            height: 300px;
            margin: 1.5rem 0;
            border-radius: 3px;
        }
        .leaflet-pane {
            z-index: 1;
        }
    }    
    a.leaflet-control-zoom-in {
        font-size: 18px !important;
        color: #3d3d3d;
    }
    a.leaflet-control-zoom-out {
        font-size: 25px! important;
        font-weight: normal;
        color: #3d3d3d;
        text-indent: 0;
    }    
    .leaflet-top, .leaflet-bottom {
        z-index: 1;
    }
</style>