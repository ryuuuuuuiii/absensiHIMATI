/* Service Worker — Absensi HIMA TI Web Push Notification */

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
    let data = {};

    if (event.data) {
        try {
            data = event.data.json();
        } catch (err) {
            data = {
                title: 'Absensi HIMA TI',
                body: event.data.text(),
            };
        }
    }

    const title = data.title || 'Absensi HIMA TI';
    const options = {
        body: data.body || 'Ada pembaruan rapat atau kegiatan baru.',
        icon: data.icon || '/assets/img/logo2.png',
        badge: data.badge || '/assets/img/logo2.png',
        tag: data.tag || 'himati-notification',
        renotify: true,
        vibrate: [150, 75, 150],
        data: {
            url: data.url || '/portal',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = (event.notification && event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/portal';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            const targetFullUrl = new URL(targetUrl, self.location.origin).href;

            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === targetFullUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    if ('navigate' in client) {
                        client.navigate(targetFullUrl);
                    }
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetFullUrl);
            }
        })
    );
});

