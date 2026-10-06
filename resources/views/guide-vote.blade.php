@extends('layouts.app')

@section('title', 'Guide du vote – Tout comprendre avant de voter')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 sm:space-y-10 px-2 sm:px-0">
    <!-- En-tête -->
    <div class="card p-5 sm:p-8 text-center">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold gradient-text mb-2">Guide du vote</h1>
        <p class="text-base sm:text-lg text-gray-600">Tout comprendre avant de voter, quel que soit votre niveau</p>
    </div>

    <!-- Introduction -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">1.</span> À quoi sert ce vote ?
        </h2>
        <p class="text-gray-700 leading-relaxed">
            Vous allez voter pour élire des candidats à différentes <strong>postes</strong> (par exemple : Présidence, Vice-Présidence, Secrétariat, etc.). Chaque poste a un ou plusieurs candidats. Vous disposez d’un certain nombre de <strong>voix</strong> pour chaque poste. Ce guide explique comment utiliser vos voix et comment les résultats sont calculés.
        </p>
    </div>

    <!-- Qui vote, combien de voix -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">2.</span> Qui peut voter et combien de voix ai-je ?
        </h2>
        <ul class="space-y-3 text-gray-700 leading-relaxed list-disc list-inside">
            <li><strong>Inscrits</strong> : ce sont toutes les personnes autorisées à voter (la liste est fixée avant l’élection).</li>
            <li><strong>Vous</strong> : vous faites partie des inscrits. Vous recevez un lien unique par e-mail pour accéder au vote.</li>
            <li><strong>Vos voix</strong> : pour chaque poste (Présidence, Vice-Présidence, etc.), vous avez un nombre de voix indiqué à l’écran, par exemple « Vous avez 3 voix pour ce poste ». Ce nombre peut être différent selon les électeurs.</li>
        </ul>
        <div class="mt-4 p-4 bg-primary-50 rounded-xl border border-primary-200">
            <p class="text-sm font-medium text-primary-800">
                Exemple : si vous avez 3 voix pour « Présidence », vous pouvez donner 1 voix à un candidat, 2 à un autre, ou 3 à un seul, ou utiliser des bulletins nuls (voir plus bas). Le total utilisé pour ce poste ne doit pas dépasser 3.
            </p>
        </div>
    </div>

    <!-- Comment voter sur l’écran -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">3.</span> Comment voter sur l’écran ?
        </h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            Pour chaque poste, vous voyez la liste des candidats et le nombre de voix dont vous disposez.
        </p>
        <ul class="space-y-3 text-gray-700 leading-relaxed">
            <li><strong>Boutons + et − à côté d’un candidat</strong> : ils permettent d’ajouter ou de retirer une voix pour ce candidat. Le chiffre affiché indique combien de voix vous lui donnez.</li>
            <li><strong>« Bulletins nuls »</strong> : si vous ne souhaitez pas donner une voix à un candidat pour ce poste, vous pouvez marquer une (ou plusieurs) voix comme « bulletin nul » avec les boutons + et − à côté de « Bulletins nuls ». Une voix en bulletin nul compte dans votre total pour le poste mais ne va à aucun candidat.</li>
            <li><strong>Voix non utilisées (bulletins blancs)</strong> : si vous n’utilisez pas toutes vos voix pour un poste (par exemple vous avez 3 voix et vous n’en donnez que 2 à des candidats ou en nuls), les voix restantes sont automatiquement comptées comme « bulletins blancs » (voix non utilisées). Vous n’avez rien à faire : le système s’en charge à la validation.</li>
        </ul>
        <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
            <p class="text-sm font-medium text-amber-800">
                Vous devez utiliser au moins une voix (candidat ou bulletin nul) pour pouvoir valider votre vote. Pensez à signer dans la zone prévue avant de cliquer sur « Valider mon vote ».
            </p>
        </div>
    </div>

    <!-- Résumé des types de voix -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">4.</span> Les différents types de voix (résumé)
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border border-gray-200 rounded-xl overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-gray-800">Type</th>
                        <th class="px-4 py-3 font-semibold text-gray-800">Explication simple</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr><td class="px-4 py-3 font-medium">Voix pour un candidat</td><td class="px-4 py-3 text-gray-700">Vous donnez une voix à un candidat (bouton + à côté de son nom).</td></tr>
                    <tr><td class="px-4 py-3 font-medium">Bulletin nul</td><td class="px-4 py-3 text-gray-700">Vous choisissez de ne pas donner cette voix à un candidat pour ce poste (bouton + à côté de « Bulletins nuls »).</td></tr>
                    <tr><td class="px-4 py-3 font-medium">Bulletin blanc (voix non utilisée)</td><td class="px-4 py-3 text-gray-700">Vous n’utilisez pas cette voix (vous en avez 3, vous n’en donnez que 2). Elle est comptée automatiquement comme « voix non utilisée ».</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Comment sont calculés les résultats -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">5.</span> Comment sont calculés les résultats ?
        </h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            Après le vote, les résultats sont affichés. Voici ce que signifient les chiffres, en termes simples.
        </p>
        <ul class="space-y-4 text-gray-700 leading-relaxed">
            <li><strong>Inscrits</strong> : nombre total de personnes autorisées à voter.</li>
            <li><strong>Votants</strong> : nombre de personnes qui ont effectivement voté.</li>
            <li><strong>Nombre de voix</strong> : total des voix (somme du « nombre de voix » de chaque votant, tel que défini dans la liste des électeurs).</li>
            <li><strong>Bulletins nuls</strong> : nombre de voix que les votants ont marquées comme « bulletin nul ».</li>
            <li><strong>Bulletins blancs</strong> : nombre de voix non utilisées (par exemple vous aviez 3 voix pour un poste et vous n’en avez utilisé que 2).</li>
            <li><strong>Suffrages exprimés</strong> : ce sont les voix qui ont été données à des candidats. On calcule : <strong>suffrages exprimés = voix des votants − bulletins nuls − bulletins blancs</strong>. Seules les personnes qui ont voté sont prises en compte ; les voix des personnes qui n’ont pas voté ne comptent pas.</li>
        </ul>
    </div>

    <!-- Pourcentage par candidat -->
    <div class="card p-5 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">6.</span> Comment est calculé le pourcentage de chaque candidat ?
        </h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            Le pourcentage d’un candidat est calculé <strong>par poste</strong> et <strong>uniquement à partir des voix des personnes qui ont voté</strong>. Les personnes qui ne votent pas ne sont pas prises en compte dans ce calcul.
        </p>
        <div class="p-4 bg-primary-50 rounded-xl border border-primary-200 mb-4">
            <p class="font-medium text-primary-900 mb-2">Formule utilisée :</p>
            <p class="text-primary-800">
                Pourcentage du candidat = (nombre de voix reçues par le candidat ÷ total des voix pour ce poste parmi les votants) × 100
            </p>
        </div>
        <p class="text-gray-700 leading-relaxed mb-4">
            <strong>Exemple avec une seule voix :</strong> un seul votant, qui dispose d'1 voix pour le poste. Il la donne à un candidat. Ce candidat reçoit donc 1 voix sur 1 voix au total pour ce poste. Son pourcentage est : <strong>1 ÷ 1 × 100 = 100 %</strong>.
        </p>
        <p class="text-gray-700 leading-relaxed mb-4">
            <strong>Exemple avec plusieurs voix :</strong> il y a 3 votants, chacun a 3 voix pour le poste « Présidence ». Un candidat est seul en lice. Seuls 2 votants votent (le 3ᵉ ne vote pas). Les 2 votants ont donc 6 voix au total pour le poste (2 × 3). Le candidat reçoit par exemple 5 voix. Son pourcentage est : <strong>5 ÷ 6 × 100 ≈ 83,33 %</strong>. Les 3 voix du 3ᵉ votant (qui n’a pas voté) ne sont pas comptées dans le calcul du pourcentage.
        </p>
        <p class="text-gray-700 leading-relaxed">
            Ainsi, les résultats reflètent uniquement le choix des personnes qui ont participé au vote.
        </p>
    </div>

    <!-- Récap et lien retour -->
    <div class="card p-5 sm:p-8 bg-gradient-to-br from-primary-50 to-white border-2 border-primary-200">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-primary-600">7.</span> En résumé
        </h2>
        <ul class="space-y-2 text-gray-700 leading-relaxed">
            <li>Vous avez un nombre de voix par poste (affiché à l’écran).</li>
            <li>Vous répartissez ces voix entre les candidats et/ou les bulletins nuls ; les voix non utilisées sont des bulletins blancs.</li>
            <li>Seuls les votants sont pris en compte dans les totaux et les pourcentages ; les non-votants ne comptent pas.</li>
            <li>Le pourcentage d’un candidat = ses voix ÷ (total des voix pour sa poste parmi les votants) × 100.</li>
            <li>N’oubliez pas de signer avant de valider votre vote.</li>
        </ul>
        <div class="mt-6 sm:mt-8 flex flex-wrap gap-3 sm:gap-4 justify-center">
            <a href="{{ route('accueil') }}" class="btn btn-primary inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Retour à l’accueil
            </a>
        </div>
    </div>
</div>
@endsection
