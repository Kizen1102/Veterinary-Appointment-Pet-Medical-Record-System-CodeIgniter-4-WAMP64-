/**
 * In-app notifications for PawRecord.
 *
 * Every few seconds this asks GET /notifications/feed for the user's alerts, then:
 *   - keeps the red number on the bell (and in the sidebar) correct,
 *   - refills the panel under the bell,
 *   - pops up a small card for an alert that arrived while the page was open.
 *
 * Nothing is sent, only read, so a closed tab or a lost connection simply changes nothing.
 */
(function () {
    'use strict';

    var script = document.currentScript || document.querySelector('script[data-feed]');
    var feedUrl = script && script.dataset.feed;
    var list = document.querySelector('[data-bell-list]');

    if (!feedUrl || !list) {
        return; // not a signed-in page
    }

    var EVERY_MS = 20000; // how often we look for new alerts
    var counts = document.querySelectorAll('[data-bell-count]');
    var readAll = document.querySelector('[data-bell-read-all]');
    var stack = document.querySelector('[data-toast-stack]');
    var known = null; // ids already seen in this tab; null until the first answer

    function showCount(unread) {
        counts.forEach(function (badge) {
            badge.textContent = unread > 9 ? '9+' : String(unread);
            badge.hidden = unread < 1;
        });

        if (readAll) {
            readAll.hidden = unread < 1;
        }
    }

    /** One row of the panel. */
    function row(item) {
        var link = document.createElement('a');
        link.className = 'bell-item' + (item.unread ? ' is-unread' : '');
        link.href = item.url;

        var icon = document.createElement('span');
        icon.className = 'bell-item-icon';
        icon.textContent = item.icon;

        var text = document.createElement('span');
        text.className = 'bell-item-text';
        text.appendChild(line('bell-item-title', item.title));

        if (item.message) {
            text.appendChild(line('bell-item-message', item.message));
        }
        text.appendChild(line('bell-item-time', item.time));

        link.appendChild(icon);
        link.appendChild(text);

        return link;
    }

    /** A piece of text. textContent, never innerHTML, so a title can never become HTML. */
    function line(className, value) {
        var span = document.createElement('span');
        span.className = className;
        span.textContent = value;

        return span;
    }

    function fillPanel(items) {
        list.textContent = '';

        if (items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'bell-empty';
            empty.appendChild(line('fs-3', '🔔'));
            empty.appendChild(document.createTextNode('No notifications yet.'));
            list.appendChild(empty);

            return;
        }

        items.forEach(function (item) {
            list.appendChild(row(item));
        });
    }

    /** The pop-up card for a brand-new alert; it goes away by itself. */
    function popUp(item) {
        if (!stack) {
            return;
        }

        var card = document.createElement('a');
        card.className = 'paw-toast';
        card.href = item.url;
        card.appendChild(line('paw-toast-icon', item.icon));

        var text = document.createElement('span');
        text.className = 'bell-item-text';
        text.appendChild(line('bell-item-title', item.title));

        if (item.message) {
            text.appendChild(line('bell-item-message', item.message));
        }
        card.appendChild(text);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'paw-toast-close';
        close.setAttribute('aria-label', 'Close');
        close.textContent = '×';
        close.addEventListener('click', function (event) {
            event.preventDefault();
            card.remove();
        });
        card.appendChild(close);

        stack.appendChild(card);
        setTimeout(function () {
            card.remove();
        }, 8000);
    }

    function refresh() {
        fetch(feedUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items)) {
                    return; // signed out, or no connection: leave the page as it is
                }

                showCount(data.unread);
                fillPanel(data.items);

                var ids = data.items.map(function (item) {
                    return item.id;
                });

                if (known !== null) {
                    data.items.filter(function (item) {
                        return item.unread && known.indexOf(item.id) === -1;
                    }).reverse().forEach(popUp);
                }
                known = ids;
            })
            .catch(function () {
                /* no connection: try again on the next round */
            });
    }

    refresh();
    setInterval(function () {
        if (!document.hidden) {
            refresh(); // a tab in the background is left alone
        }
    }, EVERY_MS);

    // Back on this tab: show what came in while it was away
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refresh();
        }
    });
})();
