    @push('styles')
        <style>
            .purchase-item-foto-cell {
                vertical-align: middle;
            }

            .purchase-item-foto {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 72px;
                padding: 0.35rem;
                border: 1px dashed var(--color-border-strong, #cbd5e1);
                border-radius: 8px;
                background: #fff;
                cursor: pointer;
                transition: border-color 0.2s ease, background 0.2s ease;
            }

            .purchase-item-foto.is-dragover {
                border-color: var(--color-sky, #0ea5e9);
                background: var(--brand-blue-pale, #f0f9ff);
            }

            .purchase-item-foto.has-file {
                border-style: solid;
            }

            .purchase-item-foto__input {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .purchase-item-foto__placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.15rem;
                text-align: center;
            }

            .purchase-item-foto__icon {
                font-size: 1.1rem;
                line-height: 1;
            }

            .purchase-item-foto__hint {
                font-size: 0.7rem;
                color: var(--color-muted, #64748b);
            }

            .purchase-item-foto__preview {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.2rem;
                width: 100%;
            }

            .purchase-item-foto__img {
                max-width: 72px;
                max-height: 52px;
                object-fit: contain;
                border-radius: 4px;
            }

            .purchase-item-foto__name {
                font-size: 0.65rem;
                color: var(--color-muted, #64748b);
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .purchase-item-foto__clear {
                border: none;
                background: transparent;
                color: var(--color-danger, #dc2626);
                font-size: 1rem;
                line-height: 1;
                cursor: pointer;
                padding: 0;
            }
        </style>
    @endpush
