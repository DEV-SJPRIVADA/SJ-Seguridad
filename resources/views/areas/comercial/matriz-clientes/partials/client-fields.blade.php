@php
    /** @var \App\Models\CommercialClient $client */
@endphp

<div class="form-stack">
    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
        <div class="form-field">
            <x-input-label for="nit" value="NIT" />
            <x-text-input id="nit" name="nit" class="form-input" :value="old('nit', $client->nit)" required />
            <x-input-error :messages="$errors->get('nit')" />
        </div>
        <div class="form-field">
            <x-input-label for="name" value="Nombre cliente" />
            <x-text-input id="name" name="name" class="form-input" :value="old('name', $client->name)" required />
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="form-field">
            <x-input-label for="phone" value="Telefono" />
            <x-text-input id="phone" name="phone" class="form-input" :value="old('phone', $client->phone)" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>
        <div class="form-field">
            <x-input-label for="city" value="Ciudad" />
            <x-text-input id="city" name="city" class="form-input" :value="old('city', $client->city)" />
            <x-input-error :messages="$errors->get('city')" />
        </div>
    </div>

    <div class="form-field">
        <x-input-label for="address" value="Direccion" />
        <x-text-input id="address" name="address" class="form-input" :value="old('address', $client->address)" />
        <x-input-error :messages="$errors->get('address')" />
    </div>

    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
        <div class="form-field">
            <x-input-label for="legal_rep_name" value="Representante legal" />
            <x-text-input id="legal_rep_name" name="legal_rep_name" class="form-input" :value="old('legal_rep_name', $client->legal_rep_name)" />
            <x-input-error :messages="$errors->get('legal_rep_name')" />
        </div>
        <div class="form-field">
            <x-input-label for="legal_rep_doc" value="CC / Doc. RL" />
            <x-text-input id="legal_rep_doc" name="legal_rep_doc" class="form-input" :value="old('legal_rep_doc', $client->legal_rep_doc)" />
            <x-input-error :messages="$errors->get('legal_rep_doc')" />
        </div>
    </div>
</div>
