(function () {
    'use strict';

    function initClientPicker(root) {
        const searchUrl = root.dataset.searchUrl;
        if (!searchUrl) {
            return;
        }

        const searchWrap = root.querySelector('.js-client-picker-search');
        const selectedWrap = root.querySelector('.js-client-picker-selected');
        const input = root.querySelector('#client_search');
        const results = root.querySelector('.js-client-picker-results');
        const hint = root.querySelector('.js-client-picker-hint');
        const hiddenId = root.querySelector('.js-client-picker-id');
        const nameEl = root.querySelector('.js-client-picker-name');
        const nitEl = root.querySelector('.js-client-picker-nit');
        const cityEl = root.querySelector('.js-client-picker-city');
        const clearBtn = root.querySelector('.js-client-picker-clear');

        let debounceTimer = null;
        let abortController = null;

        function showSelected(client) {
            hiddenId.value = String(client.id);
            nameEl.textContent = client.name || '';
            nitEl.textContent = client.nit || '';
            cityEl.textContent = client.city ? ' · ' + client.city : '';
            selectedWrap.style.display = '';
            searchWrap.style.display = 'none';
            results.hidden = true;
            results.innerHTML = '';
            if (hint) {
                hint.hidden = true;
                hint.textContent = '';
            }
            if (input) {
                input.value = '';
            }
        }

        function showSearch() {
            hiddenId.value = '';
            selectedWrap.style.display = 'none';
            searchWrap.style.display = '';
            if (input) {
                input.focus();
            }
        }

        function renderResults(items) {
            results.innerHTML = '';

            if (!items.length) {
                results.hidden = true;
                if (hint) {
                    hint.hidden = false;
                    hint.textContent = 'Sin coincidencias. Pruebe otro nombre o NIT.';
                }
                return;
            }

            if (hint) {
                hint.hidden = true;
                hint.textContent = '';
            }

            items.forEach(function (client) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'client-picker-results__item';
                button.setAttribute('role', 'option');
                button.innerHTML =
                    '<strong>' + escapeHtml(client.name) + '</strong>' +
                    '<span>NIT ' + escapeHtml(client.nit) + (client.city ? ' · ' + escapeHtml(client.city) : '') + '</span>';
                button.addEventListener('click', function () {
                    showSelected(client);
                });
                results.appendChild(button);
            });

            results.hidden = false;
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function search(term) {
            if (abortController) {
                abortController.abort();
            }

            if (term.length < 2) {
                results.hidden = true;
                results.innerHTML = '';
                if (hint) {
                    if (term.length === 0) {
                        hint.hidden = true;
                        hint.textContent = '';
                    } else {
                        hint.hidden = false;
                        hint.textContent = 'Escriba al menos 2 caracteres.';
                    }
                }
                return;
            }

            abortController = new AbortController();

            fetch(searchUrl + '?q=' + encodeURIComponent(term), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: abortController.signal,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('search_failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    renderResults(Array.isArray(payload.data) ? payload.data : []);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    results.hidden = true;
                    if (hint) {
                        hint.hidden = false;
                        hint.textContent = 'No se pudo buscar. Intente de nuevo.';
                    }
                });
        }

        if (input) {
            input.addEventListener('input', function () {
                const term = input.value.trim();
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(function () {
                    search(term);
                }, 280);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', showSearch);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-client-picker').forEach(initClientPicker);
    });
})();
