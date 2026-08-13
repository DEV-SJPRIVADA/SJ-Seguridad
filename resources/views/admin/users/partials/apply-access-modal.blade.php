<div
    id="apply-access-modal"
    class="sites-modal"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="apply-access-modal-title"
    data-target-user-name="{{ $user->name }}"
    data-open-on-load="{{ $errors->has('source_user_id') ? '1' : '0' }}"
>
    <div class="sites-modal__backdrop" data-apply-access-close></div>
    <div class="panel sites-modal__panel">
        <div class="panel__header" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <div>
                <h3 class="panel-title" id="apply-access-modal-title">Aplicar acceso de otro usuario</h3>
                <p class="panel-text">Reemplazara rol y permisos directos de <strong>{{ $user->name }}</strong>. Los datos personales no cambian.</p>
            </div>
            <button type="button" class="btn btn--secondary btn--sm" data-apply-access-close>Cerrar</button>
        </div>

        <div class="panel__body">
            <form method="POST" action="{{ route('admin.users.apply-access', $user) }}" id="apply-access-form" class="form-stack">
                @csrf

                <div class="form-field">
                    <label class="form-label" for="apply-access-source">Usuario origen</label>
                    <select name="source_user_id" id="apply-access-source" class="form-select js-copy-access-select @error('source_user_id') form-input--invalid @enderror" required>
                        <option value="">Seleccione un usuario</option>
                        @foreach ($copyCandidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected((int) old('source_user_id') === $candidate->id)>
                            {{ trim($candidate->name.' · '.$candidate->email.($candidate->document_number ? ' · '.$candidate->document_number : '').(! $candidate->is_active ? ' (inactivo)' : '')) }}
                        </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('source_user_id')" />
                </div>

                <div class="copy-access-panel__options">
                    <label class="copy-access-panel__toggle">
                        <input type="checkbox" name="include_area" value="1" @checked(old('include_area', true))>
                        <span>Incluir area base</span>
                    </label>
                    <label class="copy-access-panel__toggle">
                        <input type="checkbox" name="include_sede" value="1" @checked(old('include_sede', true))>
                        <span>Incluir sede</span>
                    </label>
                </div>

                <div class="form-actions__group" style="margin-top: 1rem;">
                    <button type="button" class="btn btn--secondary btn--sm" data-apply-access-close>Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--sm" id="apply-access-submit">Aplicar acceso</button>
                </div>
            </form>
        </div>
    </div>
</div>
