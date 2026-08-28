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

            .purchase-attachments-picker {
                margin-top: 0.5rem;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .purchase-attachments-picker__input {
                display: none;
            }

            .purchase-attachments-picker__actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .purchase-attachments-picker__btn {
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-weight: 500;
                user-select: none;
                margin: 0;
            }

            .purchase-attachments-picker__status {
                font-size: 0.85rem;
                color: var(--color-muted, #64748b);
            }

            .purchase-attachments-list {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                list-style: none;
                padding: 0;
                margin: 0.25rem 0 0 0;
            }

            .purchase-attachment-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.4rem 0.75rem;
                background: #f8fafc;
                border: 1px solid var(--color-border-subtle, #e2e8f0);
                border-radius: 6px;
                font-size: 0.85rem;
                gap: 0.5rem;
            }

            .purchase-attachment-item__info {
                display: flex;
                align-items: center;
                gap: 0.4rem;
                min-width: 0;
                overflow: hidden;
            }

            .purchase-attachment-item__name {
                font-weight: 500;
                color: var(--color-text, #1e293b);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .purchase-attachment-item__size {
                color: var(--color-muted, #64748b);
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .purchase-attachment-item__remove {
                padding: 0.15rem 0.5rem;
                font-size: 0.75rem;
                line-height: 1.2;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                flex-shrink: 0;
            }
        </style>
    @endpush
