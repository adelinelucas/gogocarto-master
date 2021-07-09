importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.1.5/workbox-sw.js');
importScripts('https://cdn.jsdelivr.net/npm/idb@4.0.5/build/iife/index-min.js');

const TILES_DOMAIN_NAMES = [
    'global.ssl.fastly.net',
    'tile.openstreetmap.se',
    'maps.wikimedia.org',
    'tiles.lyrk.org',
    'tile.openstreetmap.fr',
    'ssl.fastly.net',
    'api.mapbox.com'
];

// Routes needed to run the app
const SYMFONY_ROUTES = [
    '/appli',
    '/api/manifest',
    '/api/gogocartojs-conf.json'
];

const DEFAULT_MAPPING = ['id', ['name'], 'latitude', 'longitude', 'status', 'moderationState'];

const GOGOCARTO_DB_NAME = 'gogocarto';
const GOGOCARTO_DB_VERSION = 2; // This must be changed whenever we do changes to do the database model

const cacheCompactElements = async (db, response) => {
    const json = await response.json();

    if( json.ontology === 'gogocompact' ) {
        // Write mapping in settings store if it is not set yet, or if it has changed
        // If it has changed, all the compact elements will be deleted from cache
        let tx = await db.transaction(['settings', 'compact-elements'], 'readwrite');
        const currentMapping = await tx.objectStore('settings').get('mapping');
        if (!currentMapping || JSON.stringify(currentMapping) !== JSON.stringify(json.mapping)) {
            await Promise.all([
                tx.objectStore('settings').put({
                    id: 'mapping',
                    value: json.mapping
                }),
                tx.objectStore('compact-elements').clear(),
                tx.done
            ]);
        }

        // Insert all elements in one transaction
        // We use "put" instead of "add" so that we update the element if it already exists
        // See https://github.com/jakearchibald/idb#article-store
        tx = await db.transaction('compact-elements', 'readwrite');
        await Promise.all([
            ...json.data.map(element => tx.store.put({
                id: element[0],
                lat: element[2],
                lng: element[3],
                data: element
            })),
            tx.done
        ]);

        console.log(`Cached ${json.data.length} compact elements`);
    }
};

const UsePrecachePlugin = precache => ({
    cacheKeyWillBeUsed: async ({ request }) => {
        const url = new URL(request.url);
        return precache.getCacheKeyForURL(url.pathname);
    }
});

// We want the SW to delete outdated cache on each activation
workbox.precaching.cleanupOutdatedCaches();

// Create custom precache for Symfony routes
// The goal is to precache these routes so that they are available immediately on app install,
// but to update them when new versions are available (via the StaleWhileRevalidate strategy)
const symfonyRoutesCache = new workbox.precaching.PrecacheController({ cacheName: 'symfony-routes' });
self.addEventListener('install', event => event.waitUntil(symfonyRoutesCache.install(event)));
self.addEventListener('activate', event => event.waitUntil(symfonyRoutesCache.activate(event)));
symfonyRoutesCache.addToCacheList(SYMFONY_ROUTES.map(route => ({ url: route, revision: Date.now().toString() })));

workbox.routing.registerRoute(
    ({ url }) => SYMFONY_ROUTES.some(route => url.pathname.startsWith(route)),
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'symfony-routes',
        plugins: [ UsePrecachePlugin(symfonyRoutesCache) ]
    })
);

// Full elements cache
workbox.routing.registerRoute(
    ({ url }) => url.pathname.startsWith('/api/elements/'),
    new workbox.strategies.NetworkFirst({
        networkTimeoutSeconds: 5,
        cacheName: 'full-elements',
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 7 * 24 * 60 * 60,
                purgeOnQuotaError: true
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({ statuses: [0, 200] })
        ]
    })
);

// Compact elements cache
workbox.routing.registerRoute(
    ({ url }) => url.pathname === '/api/elements',
    async ({ url, request }) => {
        const db = await idb.openDB(GOGOCARTO_DB_NAME, GOGOCARTO_DB_VERSION, {
            upgrade(db, oldVersion) {
                // If this is a first install of the database
                if( oldVersion === 0 ) {
                    const store = db.createObjectStore('compact-elements', { keyPath: 'id' });
                    store.createIndex('lat', 'lat', { unique: false });
                    store.createIndex('lng', 'lng', { unique: false });
                }
                db.createObjectStore('settings', { keyPath: 'id' })
            }
        });

        try {
            const response = await fetch(request);

            // We don't need to await the elements to be cached before returning the response
            if (response.ok) cacheCompactElements(db, response.clone());

            return response;
        } catch(e) {
            const requestUrl = new URL(url);
            const boundsJson = requestUrl.searchParams.has('boundsJson') && JSON.parse(requestUrl.searchParams.get('boundsJson'));

            if( boundsJson && boundsJson.length > 0 ) {
                let matchingElements = [], mapping;
                let latitudeIndex = db.transaction('compact-elements').store.index('lat');

                // TODO see if we can improve performances with this solution: https://stackoverflow.com/a/32976384/7900695
                for( let bounds of boundsJson ) {
                    // Match bounding box latitude
                    let cursor = await latitudeIndex.openCursor(IDBKeyRange.bound(bounds._southWest.lat, bounds._northEast.lat));
                    while (cursor) {
                        // Match bounding box longitude
                        if (cursor.value.lng >= bounds._southWest.lng && cursor.value.lng <= bounds._northEast.lng) {
                            matchingElements.push(cursor.value.data);
                        }
                        cursor = await cursor.continue();
                    }
                }

                console.log(`Retrieved ${matchingElements.length} compact elements from cache`);

                // Retrieve mapping from settings
                try {
                    const setting = await db.get('settings', 'mapping');
                    mapping = setting.value;
                    console.log(`Using mapping from settings: ${JSON.stringify(mapping)}`);
                } catch(e) {
                    mapping = DEFAULT_MAPPING;
                    console.log(`Failed retrieving mapping from settings. Using default mapping: ${JSON.stringify(mapping)}`);
                }

                // Returns a response that matches the usual API response
                return new Response(
                    JSON.stringify({
                        data: matchingElements,
                        mapping,
                        ontology: 'gogocompact'
                    }),
                    {
                        status: 200,
                        headers: new Headers({ 'Content-Type': 'application/json' })
                    }
                );
            } else {
                // If we could not get the bounds, rethrow the error
                throw e;
            }
        }
    }
);

// Tiles cache
workbox.routing.registerRoute(
    ({ url }) => TILES_DOMAIN_NAMES.some(domainName => url.hostname.includes(domainName)),
    new workbox.strategies.CacheFirst({
        cacheName: 'tiles',
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 200,
                maxAgeSeconds: 31 * 24 * 60 * 60,
                purgeOnQuotaError: true
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({ statuses: [0, 200] })
        ]
    })
);

workbox.precaching.precacheAndRoute([]);

// Following code not working, so using simple preCacheAndRoute (see above)
// workbox.precaching.precacheAndRoute(
//     self.__WB_MANIFEST,
//     // Ignore the ?ver= query, as the resources cached by the SW are automatically updated
//     { ignoreURLParametersMatching: [/^(ver|utm_.+)$/] }
// );
