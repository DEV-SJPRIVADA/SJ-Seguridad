{{--
  Filtro multi-cédula (solo UI + query; sin historial).
  Props:
    - name: nombre del hidden (default document_numbers)
    - value: valor actual (string o list)
    - id: prefijo único (opcional)
    - submitOnApply: si true, envía el form padre al aplicar
    - max: tope de cédulas (default 500)
    - disabled: deshabilita el botón
--}}
@props([
    'name' => 'document_numbers',
    'value' => '',
    'id' => null,
    'submitOnApply' => true,
    'max' => 500,
    'disabled' => false,
])

@php
    $uid = $id ?: ('mcf-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $name));
    $modalName = 'multi-cedula-'.$uid;
    $rawValue = is_array($value) ? implode("\n", $value) : (string) $value;
    $hasActive = trim($rawValue) !== '';
    $max = max(1, (int) $max);
    $activeCount = $hasActive
        ? count(preg_split('/[\r\n,;]+/', $rawValue, -1, PREG_SPLIT_NO_EMPTY))
        : 0;
@endphp

<div
    class="multi-cedula-filter"
    data-multi-cedula-root
    data-multi-cedula-id="{{ $uid }}"
    data-multi-cedula-max="{{ $max }}"
    data-multi-cedula-submit="{{ $submitOnApply ? '1' : '0' }}"
    {{ $attributes }}
>
    <input
        type="hidden"
        name="{{ $name }}"
        value="{{ $rawValue }}"
        data-multi-cedula-hidden
        data-multi-cedula-for="{{ $uid }}"
    >

    <button
        type="button"
        class="req-manage-filters__icon-btn multi-cedula-filter__trigger {{ $hasActive ? 'req-manage-filters__icon-btn--primary' : 'req-manage-filters__icon-btn--ghost' }}"
        title="{{ $hasActive ? 'Varias cédulas (activo: '.$activeCount.')' : 'Filtrar varias cédulas' }}"
        aria-label="{{ $hasActive ? 'Varias cédulas (activo: '.$activeCount.')' : 'Filtrar varias cédulas' }}"
        data-multi-cedula-open
        data-multi-cedula-for="{{ $uid }}"
        @disabled($disabled)
        x-on:click.prevent="$dispatch('open-modal', '{{ $modalName }}')"
    >
        <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
        <span
            class="multi-cedula-filter__badge"
            data-multi-cedula-badge
            data-multi-cedula-for="{{ $uid }}"
            @if (! $hasActive) hidden @endif
        >{{ $activeCount > 99 ? '99+' : $activeCount }}</span>
    </button>

    <x-modal :name="$modalName" maxWidth="lg" focusable>
        <div
            class="modal-card ficha-empleados-masivos-modal multi-cedula-modal"
            data-multi-cedula-modal
            data-multi-cedula-for="{{ $uid }}"
        >
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
                    </span>
                    <div class="multi-cedula-modal__heading-copy">
                        <h3 class="ficha-empleados-masivos-modal__title">Filtrar varias cédulas</h3>
                        <p class="ficha-empleados-masivos-modal__lead multi-cedula-modal__lead">
                            Pegue o escriba cédulas: una por línea, o separadas por coma o punto y coma. Coincidencia exacta (máx. {{ $max }}). No se guarda historial.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="multi-cedula-modal__body">
                <label class="form-label" for="multi-cedula-textarea-{{ $uid }}">Cédulas</label>
                <textarea
                    id="multi-cedula-textarea-{{ $uid }}"
                    class="form-input multi-cedula-modal__textarea"
                    rows="12"
                    placeholder="1234567890&#10;9876543210"
                    data-multi-cedula-textarea
                    data-multi-cedula-for="{{ $uid }}"
                >{{ $rawValue }}</textarea>
                <p class="multi-cedula-modal__hint" data-multi-cedula-hint data-multi-cedula-for="{{ $uid }}">
                    0 cédula(s) reconocida(s).
                </p>
                <p
                    class="multi-cedula-modal__error"
                    data-multi-cedula-error
                    data-multi-cedula-for="{{ $uid }}"
                    hidden
                ></p>
            </div>

            <div class="multi-cedula-modal__actions">
                <button
                    type="button"
                    class="btn btn--secondary btn--sm"
                    data-multi-cedula-clear
                    data-multi-cedula-for="{{ $uid }}"
                >
                    Quitar filtro
                </button>
                <button
                    type="button"
                    class="btn btn--secondary btn--sm"
                    x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="btn btn--primary btn--sm"
                    data-multi-cedula-apply
                    data-multi-cedula-for="{{ $uid }}"
                >
                    <x-lucide-search width="15" height="15" aria-hidden="true" />
                    Aplicar
                </button>
            </div>
        </div>
    </x-modal>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function parseDocuments(raw, max) {
                    const parts = String(raw || '').split(/[\r\n,;]+/);
                    const seen = {};
                    const docs = [];
                    const limit = Math.max(1, Number(max) || 500);

                    parts.forEach(function (part) {
                        const document = String(part || '').trim();
                        if (!document) {
                            return;
                        }
                        const digits = document.replace(/\D+/g, '');
                        const normalized = digits !== '' ? digits : document.toLowerCase();
                        if (!normalized || seen[normalized]) {
                            return;
                        }
                        if (docs.length >= limit) {
                            return;
                        }
                        seen[normalized] = true;
                        docs.push(document);
                    });

                    return docs;
                }

                function normalizeDocument(document) {
                    const digits = String(document || '').replace(/\D+/g, '');
                    return digits !== '' ? digits : String(document || '').trim().toLowerCase();
                }

                function rootFor(uid) {
                    return document.querySelector('[data-multi-cedula-root][data-multi-cedula-id="' + uid + '"]');
                }

                function hiddenFor(uid) {
                    return document.querySelector('[data-multi-cedula-hidden][data-multi-cedula-for="' + uid + '"]');
                }

                function textareaFor(uid) {
                    return document.querySelector('[data-multi-cedula-textarea][data-multi-cedula-for="' + uid + '"]');
                }

                function hintFor(uid) {
                    return document.querySelector('[data-multi-cedula-hint][data-multi-cedula-for="' + uid + '"]');
                }

                function errorFor(uid) {
                    return document.querySelector('[data-multi-cedula-error][data-multi-cedula-for="' + uid + '"]');
                }

                function openBtnFor(uid) {
                    return document.querySelector('[data-multi-cedula-open][data-multi-cedula-for="' + uid + '"]');
                }

                function badgeFor(uid) {
                    return document.querySelector('[data-multi-cedula-badge][data-multi-cedula-for="' + uid + '"]');
                }

                function updateHint(uid) {
                    const root = rootFor(uid);
                    const textarea = textareaFor(uid);
                    const hint = hintFor(uid);
                    if (!root || !textarea || !hint) {
                        return;
                    }
                    const max = Number(root.getAttribute('data-multi-cedula-max') || 500);
                    const docs = parseDocuments(textarea.value, max + 1);
                    const over = docs.length > max;
                    hint.textContent = Math.min(docs.length, max) + ' cédula(s) reconocida(s)'
                        + (over ? ' (máximo ' + max + ')' : '') + '.';
                    hint.classList.toggle('multi-cedula-modal__hint--warn', over);
                }

                function setButtonActive(uid, active, count) {
                    const btn = openBtnFor(uid);
                    if (!btn) {
                        return;
                    }
                    btn.classList.toggle('req-manage-filters__icon-btn--primary', active);
                    btn.classList.toggle('req-manage-filters__icon-btn--ghost', !active);
                    const label = active
                        ? ('Varias cédulas (activo: ' + count + ')')
                        : 'Filtrar varias cédulas';
                    btn.setAttribute('title', label);
                    btn.setAttribute('aria-label', label);

                    const badge = badgeFor(uid);
                    if (badge) {
                        if (active && count > 0) {
                            badge.hidden = false;
                            badge.textContent = count > 99 ? '99+' : String(count);
                        } else {
                            badge.hidden = true;
                            badge.textContent = '0';
                        }
                    }
                }

                function showError(uid, message) {
                    const el = errorFor(uid);
                    if (!el) {
                        return;
                    }
                    if (!message) {
                        el.hidden = true;
                        el.textContent = '';
                        return;
                    }
                    el.hidden = false;
                    el.textContent = message;
                }

                function applyFilter(uid, documents) {
                    const root = rootFor(uid);
                    const hidden = hiddenFor(uid);
                    if (!root || !hidden) {
                        return;
                    }

                    const raw = documents.join(',');
                    hidden.value = raw;
                    setButtonActive(uid, documents.length > 0, documents.length);
                    showError(uid, '');

                    window.dispatchEvent(new CustomEvent('multi-cedula-applied', {
                        detail: {
                            id: uid,
                            name: hidden.getAttribute('name') || 'document_numbers',
                            documents: documents,
                            raw: raw,
                            root: root,
                        },
                    }));

                    if (root.getAttribute('data-multi-cedula-submit') === '1') {
                        const form = root.closest('form');
                        if (form) {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                            return;
                        }
                    }

                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'multi-cedula-' + uid }));
                }

                function clearFilter(uid) {
                    const textarea = textareaFor(uid);
                    if (textarea) {
                        textarea.value = '';
                    }
                    updateHint(uid);
                    applyFilter(uid, []);
                }

                document.addEventListener('input', function (event) {
                    const target = event.target;
                    if (!target || !target.matches || !target.matches('[data-multi-cedula-textarea]')) {
                        return;
                    }
                    updateHint(target.getAttribute('data-multi-cedula-for'));
                });

                document.addEventListener('click', function (event) {
                    const applyBtn = event.target.closest('[data-multi-cedula-apply]');
                    if (applyBtn) {
                        const uid = applyBtn.getAttribute('data-multi-cedula-for');
                        const root = rootFor(uid);
                        const textarea = textareaFor(uid);
                        if (!root || !textarea) {
                            return;
                        }
                        const max = Number(root.getAttribute('data-multi-cedula-max') || 500);
                        const rawParts = String(textarea.value || '').split(/[\r\n,;]+/).filter(function (p) {
                            return String(p || '').trim() !== '';
                        });
                        if (rawParts.length > max) {
                            showError(uid, 'Máximo ' + max + ' cédulas. Reduzca la lista.');
                            if (typeof window.showToast === 'function') {
                                window.showToast('Máximo ' + max + ' cédulas.', 'error');
                            }
                            return;
                        }
                        const docs = parseDocuments(textarea.value, max);
                        if (docs.length === 0) {
                            showError(uid, 'Ingrese al menos una cédula válida.');
                            return;
                        }
                        applyFilter(uid, docs);
                        return;
                    }

                    const clearBtn = event.target.closest('[data-multi-cedula-clear]');
                    if (clearBtn) {
                        clearFilter(clearBtn.getAttribute('data-multi-cedula-for'));
                    }
                });

                document.addEventListener('open-modal', function (event) {
                    const name = typeof event.detail === 'string' ? event.detail : (event.detail && event.detail.name);
                    if (!name || String(name).indexOf('multi-cedula-') !== 0) {
                        return;
                    }
                    const uid = String(name).replace('multi-cedula-', '');
                    const hidden = hiddenFor(uid);
                    const textarea = textareaFor(uid);
                    if (hidden && textarea && !textarea.value && hidden.value) {
                        textarea.value = String(hidden.value).split(',').join('\n');
                    }
                    updateHint(uid);
                    showError(uid, '');
                });

                window.MultiCedulaFilter = {
                    parseDocuments: parseDocuments,
                    normalizeDocument: normalizeDocument,
                };
            })();
        </script>
    @endpush
@endonce
