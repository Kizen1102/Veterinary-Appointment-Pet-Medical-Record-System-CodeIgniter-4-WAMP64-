/**
 * In-app notifications for PawRecord.
 *
 * Every few seconds this asks GET /notifications/feed for the user's alerts, then:
 *   - keeps the red number on the bell (and in the sidebar) correct,
 *   - refills the panel under the bell, grouped by day,
 *   - pops up a small card for an alert that arrived while the page was open.
 *
 * The dot at the right of an alert turns it read or unread without closing the panel.
 * Without JavaScript the same dot still works: it is a normal form that reloads the page.
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
    var csrf = null;  // the token of this moment, for the forms drawn below

    function showCount(unread) {
        counts.forEach(function (badge) {
            badge.textContent = badge.classList.contains('bell-head-count')
                ? String(unread)
                : (unread > 9 ? '9+' : String(unread));
            badge.hidden = unread < 1;
        });

        if (readAll) {
            readAll.hidden = unread < 1;
        }
    }

    /** A piece of text. textContent, never innerHTML, so a title can never become HTML. */
    function line(tag, className, value) {
        var node = document.createElement(tag);
        node.className = className;

        if (value !== undefined) {
            node.textContent = value;
        }

        return node;
    }

    /** The pet's picture, with the kind of alert on its corner when there is a pet. */
    function avatarOf(item) {
        var avatar = line('span', 'bell-avatar', item.avatar);

        if (item.badge) {
            avatar.appendChild(line('span', 'bell-avatar-badge', item.badge));
        }

        return avatar;
    }

    /** The grey bar above a day's alerts. */
    function dayBar(label) {
        return line('div', 'bell-day', label);
    }

    /** One alert: pet picture, what happened, the VIEW link, and the read/unread dot. */
    function row(item) {
        var card = line('div', 'bell-item' + (item.unread ? ' is-unread' : ''));

        card.appendChild(avatarOf(item));

        var text = line('div', 'bell-item-text');

        var title = line('a', 'bell-item-title', item.title);
        title.href = item.url;
        text.appendChild(title);

        if (item.message) {
            text.appendChild(line('div', 'bell-item-message', item.message));
        }

        if (item.action) {
            var action = line('a', 'bell-item-action', item.action);
            action.href = item.url;
            text.appendChild(action);
        }

        var meta = line('div', 'bell-item-meta');

        if (item.pet) {
            var pet = line('span', 'bell-item-pet', 'Pet: ');
            pet.appendChild(line('strong', '', item.pet));
            meta.appendChild(pet);
        }
        meta.appendChild(line('span', '', item.time));
        text.appendChild(meta);
        card.appendChild(text);

        card.appendChild(dotForm(item));

        return card;
    }

    /** The dot is a real form, so it still works when JavaScript does not run. */
    function dotForm(item) {
        var form = document.createElement('form');
        form.className = 'm-0';
        form.method = 'post';
        form.action = item.toggle;

        if (csrf) {
            var token = document.createElement('input');
            token.type = 'hidden';
            token.name = csrf.name;
            token.value = csrf.hash;
            form.appendChild(token);
        }

        var dot = document.createElement('button');
        dot.type = 'submit';
        dot.className = 'bell-dot' + (item.unread ? ' is-unread' : '');
        dot.title = item.unread ? 'Mark as read' : 'Mark as unread';
        dot.setAttribute('aria-label', dot.title);
        form.appendChild(dot);

        return form;
    }

    function fillPanel(items) {
        list.textContent = '';

        if (items.length === 0) {
            var empty = line('div', 'bell-empty');
            empty.appendChild(line('div', 'fs-3', '🔔'));
            empty.appendChild(document.createTextNode('No notifications yet.'));
            list.appendChild(empty);

            return;
        }

        var day = null;
        items.forEach(function (item) {
            if (item.day !== day) {
                day = item.day;
                list.appendChild(dayBar(day));
            }
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

        card.appendChild(avatarOf(item));

        var text = line('span', 'bell-item-text');
        text.appendChild(line('span', 'bell-item-title', item.title));

        if (item.message) {
            text.appendChild(line('span', 'bell-item-message', item.message));
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
        return fetch(feedUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items)) {
                    return; // signed out, or no connection: leave the page as it is
                }

                csrf = data.csrf || csrf;
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

    // Pressing a dot inside the panel: send it quietly and redraw, so the panel stays open
    list.addEventListener('submit', function (event) {
        var form = event.target.closest('form');

        if (!form) {
            return;
        }

        event.preventDefault();
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: new FormData(form),
        }).then(function () {
            return refresh(); // the answer carries the new count and a fresh token
        }).catch(function () {
            form.submit(); // no connection for the quiet way: let the browser do it
        });
    });

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
