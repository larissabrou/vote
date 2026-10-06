@extends('layouts.app')

@section('title', 'Inscription votant - ' . $election->title)

@section('content')
<div class="max-w-2xl mx-auto space-y-6 px-2 sm:px-0">
    <div class="card p-6 sm:p-8 text-center">
        <h1 class="text-2xl sm:text-3xl font-extrabold gradient-text mb-2">Inscription des votants</h1>
        <p class="text-gray-600">{{ $election->title }}</p>
        <p class="text-sm text-gray-500 mt-2">Saisissez votre nom, prénom et email pour être ajouté à la liste des votants. Nom et prénom : lettres, espaces, tiret (-) et apostrophe (’) uniquement (pas de chiffres ni d’autres signes).</p>
    </div>

    @if($registrationClosed)
        <div class="card p-6 border-2 border-red-200 bg-red-50 text-center">
            <p class="text-red-700 font-semibold">Les inscriptions sont fermées pour cette élection.</p>
        </div>
    @else
        <div class="card p-6 sm:p-8">
            <form action="{{ $signedPostUrl }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="last_name">Nom *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required value="{{ old('last_name') }}" placeholder="VOTRE NOM">
                        @error('last_name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="first_name">Prénom *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required value="{{ old('first_name') }}" placeholder="VOTRE PRENOM">
                        @error('first_name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Votre email *</label>
                    <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}" placeholder="exemple@domaine.com">
                    @error('email')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary w-full">S'inscrire</button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    function normalizeVoterName(value) {
        var s = String(value)
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase();
        return s.replace(/[^A-Z\-' ]/g, '');
    }
    function bindNameField(id) {
        var el = document.getElementById(id);
        if (!el) return;
        function apply() {
            el.value = normalizeVoterName(el.value);
        }
        el.addEventListener('input', apply);
        el.addEventListener('blur', apply);
        if (el.value) {
            apply();
        }
    }
    bindNameField('last_name');
    bindNameField('first_name');
})();
</script>
@endpush
