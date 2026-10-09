<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Candidate;
use App\Models\Voter;
use App\Models\VoterPosition;
use App\Exports\ElecteursListExport;
use App\Exports\VotantsTemplateExport;
use App\Services\ExcelImportService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ElectionController extends Controller
{
    /** Dossier public pour logos et photos (évite conflit avec la route /elections). */
    public const PUBLIC_ASSETS_DIR = 'election_assets';

    protected $excelImportService;
    protected $emailService;

    public function __construct(ExcelImportService $excelImportService, EmailService $emailService)
    {
        $this->excelImportService = $excelImportService;
        $this->emailService = $emailService;
    }

    public function index()
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }

        if (request()->get('payment_success') === '1') {
            return redirect()->route('elections.liste')->with('success', 'Paiement effectué avec succès. L\'élection a été activée et les liens de vote ont été envoyés aux votants.');
        }

        $elections = Election::with(['candidates', 'voters'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        foreach ($elections as $election) {
            $election->updateStatus();
        }

        return view('elections.index', compact('elections'));
    }

    public function create()
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        return view('elections.create');
    }

    /**
     * Affiche la page publique d'inscription des votants.
     */
    public function showPublicVoterRegistration(Election $election)
    {
        if (!$this->isPublicRegistrationOpen($election)) {
            return view('voters.public-register', [
                'election' => $election,
                'registrationClosed' => true,
                'signedPostUrl' => URL::signedRoute('votants.inscription.enregistrer', $election),
            ]);
        }

        return view('voters.public-register', [
            'election' => $election,
            'registrationClosed' => false,
            'signedPostUrl' => URL::signedRoute('votants.inscription.enregistrer', $election),
        ]);
    }

    /**
     * Enregistre un votant depuis la page publique (email + 1 voix par défaut).
     */
    public function storePublicVoterRegistration(Request $request, Election $election)
    {
        if (!$this->isPublicRegistrationOpen($election)) {
            return back()->with('error', 'Les inscriptions sont fermées pour cette élection.');
        }

        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ], [
            'last_name.required' => 'Le nom est obligatoire.',
            'first_name.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
        ]);

        $lastName = Str::upper(Str::ascii(trim((string) $validated['last_name']), 'fr'));
        $firstName = Str::upper(Str::ascii(trim((string) $validated['first_name']), 'fr'));
        $namePattern = '/^[A-Z \'\-]+$/';
        if (trim($lastName) === '' || !preg_match($namePattern, $lastName)) {
            throw ValidationException::withMessages([
                'last_name' => ['Le nom ne peut contenir que des lettres, des espaces, des tirets ou des apostrophes.'],
            ]);
        }
        if (trim($firstName) === '' || !preg_match($namePattern, $firstName)) {
            throw ValidationException::withMessages([
                'first_name' => ['Le prénom ne peut contenir que des lettres, des espaces, des tirets ou des apostrophes.'],
            ]);
        }
        $email = strtolower(trim($validated['email']));
        $alreadyExists = Voter::where('election_id', $election->id)->whereRaw('LOWER(email) = ?', [$email])->exists();
        if ($alreadyExists) {
            return back()->with('info', 'Cette adresse email est déjà inscrite pour cette élection.');
        }

        $positions = $election->candidates()->distinct()->pluck('position')->filter()->values()->all();
        if (empty($positions)) {
            return back()->with('error', 'Aucun poste n\'est configuré pour cette élection.');
        }

        DB::transaction(function () use ($election, $email, $lastName, $firstName, $positions) {
            $voter = new Voter();
            $voter->election_id = $election->id;
            $voter->email = $email;
            $voter->first_name = $firstName;
            $voter->last_name = $lastName;
            $voter->vote_count = 1;
            $voter->token = Voter::generateToken();
            $voter->save();

            foreach ($positions as $position) {
                $vp = new VoterPosition();
                $vp->voter_id = $voter->id;
                $vp->position = $position;
                $vp->vote_count = 1;
                $vp->save();
            }
        });

        return back()->with('success', 'Inscription enregistrée. Votre email a bien été pris en compte.');
    }

    /**
     * Inscription publique ouverte jusqu'au début effectif de l'élection.
     */
    private function isPublicRegistrationOpen(Election $election): bool
    {
        if ($election->hasEnded()) {
            return false;
        }

        $start = \Carbon\Carbon::parse(
            $election->election_date->format('Y-m-d') . ' ' . ($election->start_time ?? '00:00:00')
        );

        return now()->lt($start);
    }

    /**
     * Télécharger le modèle Excel pour la liste des votants.
     */
    public function downloadVotantsTemplate()
    {
        return Excel::download(new VotantsTemplateExport(), 'modele_votants.xlsx');
    }

    public function store(Request $request)
    {
        // Convertir les dates du format JJ/MM/AAAA (ex. 23/02/2026) vers Y-m-d
        if ($request->filled('election_date')) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('d/m/Y', trim($request->election_date));
                $request->merge(['election_date' => $parsed->format('Y-m-d')]);
            } catch (\Exception $e) {
                // Laisser la valeur telle quelle, la validation renverra une erreur
            }
        }
        if ($request->filled('end_date')) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('d/m/Y', trim($request->end_date));
                $request->merge(['end_date' => $parsed->format('Y-m-d')]);
            } catch (\Exception $e) {
                $request->merge(['end_date' => null]);
            }
        } else {
            $request->merge(['end_date' => null]);
        }

        // Combiner les heures et minutes si elles sont envoyées séparément
        if ($request->has('start_time_hour') && $request->has('start_time_minute')) {
            $request->merge([
                'start_time' => $request->start_time_hour . ':' . $request->start_time_minute
            ]);
        }
        
        if ($request->has('end_time_hour') && $request->has('end_time_minute')) {
            $request->merge([
                'end_time' => $request->end_time_hour . ':' . $request->end_time_minute
            ]);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'election_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:election_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                Rule::when(
                    !$request->filled('end_date') || $request->end_date === $request->election_date,
                    ['after:start_time'],
                    []
                ),
            ],
            'candidates' => 'required|array|min:1',
            'candidates.*.first_name' => 'required|string|max:255',
            'candidates.*.last_name' => 'required|string|max:255',
            'candidates.*.position' => 'required|string|max:255',
            'candidates.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'voters_source' => 'nullable|in:link,excel',
            'voters_file' => 'nullable|mimes:xlsx,xls|max:10240|required_if:voters_source,excel',
        ], [
            'candidates.*.photo.max' => 'La photo du candidat ne doit pas dépasser 2 Mo (2048 Ko).',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo (2048 Ko).',
            'voters_file.max' => 'Le fichier Excel ne doit pas dépasser 10 Mo.',
            'voters_file.required_if' => 'Le fichier Excel est obligatoire si vous choisissez le mode import Excel.',
        ]);

        if ($validator->fails()) {
            // Sauvegarder les données des candidats dans la session
            $request->session()->flash('candidates_data', $request->candidates);
            return back()->withErrors($validator)->withInput();
        }

        // Variables pour stocker les chemins des fichiers uploadés (pour nettoyage en cas d'erreur)
        $uploadedFiles = [
            'logo' => null,
            'candidates_photos' => []
        ];

        try {
            // Utiliser DB::transaction() pour gérer automatiquement le commit/rollback
            $result = DB::transaction(function () use ($request, &$uploadedFiles) {
                // Créer l'élection
                $election = new Election();
                $election->title = $request->title;
                $election->election_date = $request->election_date;
                $election->end_date = $request->filled('end_date') ? $request->end_date : null;
                $election->start_time = $request->start_time;
                $election->end_time = $request->end_time;
                $election->status = 'pending';
                $election->voters_source = $request->input('voters_source', 'link');

                if ($request->hasFile('logo')) {
                    $dir = public_path(self::PUBLIC_ASSETS_DIR . '/logos');
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = time() . '_' . Str::random(10) . '.' . $request->file('logo')->getClientOriginalExtension();
                    $request->file('logo')->move($dir, $filename);
                    $path = self::PUBLIC_ASSETS_DIR . '/logos/' . $filename;
                    $uploadedFiles['logo'] = $path;
                    $election->logo = $path;
                }

                $election->user_id = Auth::id();
                $election->save();

                // Créer les candidats
                foreach ($request->candidates as $index => $candidateData) {
                    $candidate = new Candidate();
                    $candidate->election_id = $election->id;
                    $candidate->first_name = $candidateData['first_name'];
                    $candidate->last_name = $candidateData['last_name'];
                    $candidate->position = $candidateData['position'];

                    if ($request->hasFile("candidates.{$index}.photo")) {
                        $file = $request->file("candidates.{$index}.photo");
                        $directory = public_path(self::PUBLIC_ASSETS_DIR . '/photos');
                        if (!file_exists($directory)) {
                            mkdir($directory, 0755, true);
                        }
                        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                        $file->move($directory, $filename);
                        $photoPath = self::PUBLIC_ASSETS_DIR . '/photos/' . $filename;
                        $candidate->photo = $photoPath;
                        $uploadedFiles['candidates_photos'][] = $photoPath;
                    }

                    $candidate->save();
                }

                $votersCreated = 0;
                if (($request->input('voters_source', 'link') === 'excel') && $request->hasFile('voters_file')) {
                    $votersData = $this->excelImportService->import($request->file('voters_file'));
                    $positions = $election->candidates()->distinct()->pluck('position')->toArray();

                    foreach ($votersData as $voterData) {
                        $voteCount = max(1, (int) ($voterData['nombre_voix'] ?? $voterData['vote_count'] ?? 1));
                        $voter = new Voter();
                        $voter->election_id = $election->id;
                        $voter->email = strtolower(trim((string) $voterData['email']));
                        $voter->first_name = $voterData['nom'] ?? $voterData['first_name'] ?? '';
                        $voter->last_name = $voterData['prenom'] ?? $voterData['last_name'] ?? '';
                        $voter->vote_count = $voteCount;
                        $voter->token = Voter::generateToken();
                        $voter->save();

                        foreach ($positions as $position) {
                            $voterPosition = new VoterPosition();
                            $voterPosition->voter_id = $voter->id;
                            $voterPosition->position = $position;
                            $voterPosition->vote_count = $voteCount;
                            $voterPosition->save();
                        }
                        $votersCreated++;
                    }
                }

                return [
                    'election' => $election,
                    'votersCreated' => $votersCreated,
                ];
            });

            $election = $result['election'];
            $election->load('voters');

            $publicRegistrationUrl = URL::signedRoute('votants.inscription', $election);
            $importedFromExcel = ($request->input('voters_source', 'link') === 'excel') && ($result['votersCreated'] ?? 0) > 0;

            // Paiement désactivé : l'élection est active d'office, mais les liens de vote
            // ne partent pas tout seuls : l'administrateur déclenche l'envoi collectif.
            if (!config('billing.enabled')) {
                if ($importedFromExcel) {
                    return redirect()->route('elections.electeurs', $election)->with(
                        'success',
                        'Élection créée. ' . $result['votersCreated'] . ' votant(s) importé(s). '
                            . 'Aucun lien n’a encore été envoyé : utilisez le bouton d’envoi en masse ci-dessous quand vous êtes prêt.'
                    );
                }

                return redirect()->route('elections.voir', $election)->with(
                    'success',
                    'Élection créée. Partagez le lien d\'inscription des votants : ' . $publicRegistrationUrl . '.'
                );
            }

            // Ne pas envoyer les emails ici : ils seront envoyés après paiement (activation)
            if ($importedFromExcel) {
                $successMessage = 'Élection créée. ' . $result['votersCreated'] . ' votant(s) importé(s). Après paiement, les liens seront envoyés automatiquement.';
            } else {
                $successMessage = 'Élection créée. Après paiement, partagez le lien d\'inscription des votants : ' . $publicRegistrationUrl . '.';
            }

            return redirect()->route('paiement.election.activer', $election)->with('success', $successMessage);

        } catch (\Throwable $e) {
            // En cas d'erreur, nettoyer les fichiers uploadés
            $this->cleanupUploadedFiles($uploadedFiles);

            $request->session()->flash('candidates_data', $request->candidates);

            $msg = $e->getMessage();
            if (strpos($msg, 'Excel') !== false || strpos($msg, 'importation') !== false
                || strpos($msg, 'votant') !== false || strpos($msg, 'modèle') !== false
                || strpos($msg, 'colonne') !== false || strpos($msg, 'en-têtes') !== false
                || strpos($msg, 'modèle attendu') !== false) {
                return redirect()->route('elections.creer')
                    ->withErrors(['voters_file' => $msg])
                    ->withInput();
            }
            // Autre erreur : message générique, rester sur la page de création
            \Log::error('Erreur création élection', ['message' => $msg, 'trace' => $e->getTraceAsString()]);
            return redirect()->route('elections.creer')
                ->withErrors(['error' => 'Une erreur est survenue lors de la création de l\'élection. ' . $msg])
                ->withInput();
        }
    }

    public function show(Election $election)
    {
        try {
            if (!Auth::guard('web')->check()) {
                return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
            }
            if ($election->user_id !== Auth::id()) {
                return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
            }

            $election->load(['candidates.votes', 'voters.positions', 'votes']);
            $election->updateStatus();
            
            // Nombre de voix (affiché) = somme du vote_count des votants qui ont voté
            $total_voices = $election->voters()
                ->where('has_voted', true)
                ->sum('vote_count');

            // Bulletins blancs = voix non utilisées (candidate_id null, is_null false)
            $blank_votes = $election->votes()
                ->whereNull('candidate_id')
                ->where('is_null', false)
                ->count();

            $voted_count = $election->voted_count ?? 0;
            $null_votes = $election->null_votes ?? 0;

            // Suffrages exprimés = voix attribuées à des candidats, plafonné au total de voix (ne peut pas dépasser le nombre de voix)
            $valid_votes_raw = $election->votes()
                ->where('is_null', false)
                ->whereNotNull('candidate_id')
                ->count();
            $valid_votes = min($valid_votes_raw, (int) $total_voices);

            // Voix par position = total des voix attribuées à cette position par les votants ayant effectivement voté
            // (on n'inclut pas les voix des inscrits qui n'ont pas voté dans le dénominateur du pourcentage)
            $voterIdsAyantVote = $election->voters()->where('has_voted', true)->pluck('id');
            $voixParPosition = DB::table('voter_positions')
                ->whereIn('voter_id', $voterIdsAyantVote)
                ->groupBy('position')
                ->selectRaw('position, sum(vote_count) as total')
                ->pluck('total', 'position')
                ->all();

            $candidates = $election->candidates->map(function ($candidate) use ($voixParPosition) {
                $voteCount = $candidate->vote_count ?? 0;
                $position = $candidate->position ?? 'Autre';
                $voixPosition = $voixParPosition[$position] ?? 0;

                // Pourcentage = (voix reçues / voix attribuées à cette position) × 100 (dénominateur = total par position, variable par élection)
                $percentage = $voixPosition > 0 ? ($voteCount / $voixPosition) * 100 : 0;

                return [
                    'id' => $candidate->id,
                    'name' => $candidate->full_name ?? 'Candidat sans nom',
                    'position' => $position,
                    'photo' => $candidate->photo,
                    'votes' => $voteCount,
                    'total_voices' => (int) $voixPosition,
                    'percentage' => round($percentage, 2),
                ];
            });

            $votersEmailFailedCount = $election->isActivated()
                ? $election->voters()
                    ->whereNotNull('email_error')
                    ->where('email_error', '!=', '')
                    ->count()
                : 0;

            // Votants qui n'ont encore reçu aucune tentative d'envoi.
            $votersEmailPendingCount = $election->isActivated()
                ? $election->voters()
                    ->whereNull('email_sent_at')
                    ->where(function ($q) {
                        $q->whereNull('email_error')->orWhere('email_error', '');
                    })
                    ->count()
                : 0;

            $data = [
                'election' => $election,
                'total_voters' => $election->total_voters ?? 0,
                'voted_count' => $voted_count,
                'null_votes' => $null_votes,
                'blank_votes' => $blank_votes,
                'total_voices' => $total_voices,
                'valid_votes' => $valid_votes,
                'candidates' => $candidates->toArray(),
                'progress' => ($election->total_voters ?? 0) > 0 
                    ? round(($voted_count / $election->total_voters) * 100, 2) 
                    : 0,
                'voters_email_failed_count' => $votersEmailFailedCount,
                'voters_email_pending_count' => $votersEmailPendingCount,
            ];
            
            return view('elections.show', compact('data'));
        } catch (\Exception $e) {
            // Logger l'erreur pour le débogage
            \Log::error('Erreur dans ElectionController::show', [
                'election_id' => $election->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Rediriger avec un message d'erreur
            return redirect()->route('elections.liste')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de l\'élection. Veuillez vérifier les logs pour plus de détails.');
        }
    }

    public function activate(Election $election)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }

        $election->status = 'active';
        $election->save();

        return back()->with('success', 'Élection activée avec succès!');
    }

    public function edit(Election $election)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if (!$election->isEditable()) {
            $message = $election->hasEnded()
                ? 'Une élection déjà passée ne peut pas être modifiée.'
                : 'Cette élection ne peut plus être modifiée (déjà payée et des votes ont été enregistrés).';
            return redirect()->route('elections.voir', $election)->with('error', $message);
        }

        $election->load('candidates');
        return view('elections.edit', compact('election'));
    }

    public function update(Request $request, Election $election)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if (!$election->isEditable()) {
            $message = $election->hasEnded()
                ? 'Une élection déjà passée ne peut pas être modifiée.'
                : 'Cette élection ne peut plus être modifiée (déjà payée et des votes ont été enregistrés).';
            return redirect()->route('elections.voir', $election)->with('error', $message);
        }

        // Convertir les dates du format JJ/MM/AAAA (ex. 23/02/2026) vers Y-m-d
        if ($request->filled('election_date')) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('d/m/Y', trim($request->election_date));
                $request->merge(['election_date' => $parsed->format('Y-m-d')]);
            } catch (\Exception $e) {
                // Laisser la valeur telle quelle, la validation renverra une erreur
            }
        }
        if ($request->filled('end_date')) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('d/m/Y', trim($request->end_date));
                $request->merge(['end_date' => $parsed->format('Y-m-d')]);
            } catch (\Exception $e) {
                $request->merge(['end_date' => null]);
            }
        } else {
            $request->merge(['end_date' => null]);
        }

        if ($request->has('start_time_hour') && $request->has('start_time_minute')) {
            $request->merge(['start_time' => $request->start_time_hour . ':' . $request->start_time_minute]);
        }
        if ($request->has('end_time_hour') && $request->has('end_time_minute')) {
            $request->merge(['end_time' => $request->end_time_hour . ':' . $request->end_time_minute]);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'election_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:election_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                Rule::when(
                    !$request->filled('end_date') || $request->end_date === $request->election_date,
                    ['after:start_time'],
                    []
                ),
            ],
            'candidates' => 'required|array|min:1',
            'candidates.*.first_name' => 'required|string|max:255',
            'candidates.*.last_name' => 'required|string|max:255',
            'candidates.*.position' => 'required|string|max:255',
            'candidates.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'voters_file' => 'nullable|mimes:xlsx,xls|max:10240',
        ];
        $messages = [
            'candidates.*.photo.max' => 'La photo du candidat ne doit pas dépasser 2 Mo (2048 Ko).',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo (2048 Ko).',
            'voters_file.max' => 'Le fichier Excel ne doit pas dépasser 10 Mo.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->session()->flash('candidates_data', $request->candidates);
            return back()->withErrors($validator)->withInput();
        }

        $uploadedFiles = ['logo' => null, 'candidates_photos' => []];

        try {
            DB::transaction(function () use ($request, $election, &$uploadedFiles) {
                $election->title = $request->title;
                $election->election_date = $request->election_date;
                $election->end_date = $request->filled('end_date') ? $request->end_date : null;
                $election->start_time = $request->start_time;
                $election->end_time = $request->end_time;

                if ($request->hasFile('logo')) {
                    if ($election->logo) {
                        $oldPath = public_path($election->logo);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        } elseif (Storage::disk('public')->exists($election->logo)) {
                            Storage::disk('public')->delete($election->logo);
                        }
                    }
                    $dir = public_path(self::PUBLIC_ASSETS_DIR . '/logos');
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $filename = time() . '_' . Str::random(10) . '.' . $request->file('logo')->getClientOriginalExtension();
                    $request->file('logo')->move($dir, $filename);
                    $path = self::PUBLIC_ASSETS_DIR . '/logos/' . $filename;
                    $uploadedFiles['logo'] = $path;
                    $election->logo = $path;
                }
                $election->save();

                // Conserver les anciennes photos par index (pour les candidats sans nouvelle photo)
                $oldPhotosByIndex = $election->candidates()->orderBy('id')->pluck('photo')->all();

                // Supprimer les anciens candidats (et leurs votes s'il y en a - aucun si isEditable)
                $election->candidates()->delete();

                // Créer les nouveaux candidats
                foreach ($request->candidates as $index => $candidateData) {
                    $candidate = new Candidate();
                    $candidate->election_id = $election->id;
                    $candidate->first_name = $candidateData['first_name'];
                    $candidate->last_name = $candidateData['last_name'];
                    $candidate->position = $candidateData['position'];

                    if ($request->hasFile("candidates.{$index}.photo")) {
                        $file = $request->file("candidates.{$index}.photo");
                        $directory = public_path(self::PUBLIC_ASSETS_DIR . '/photos');
                        if (!file_exists($directory)) {
                            mkdir($directory, 0755, true);
                        }
                        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                        $file->move($directory, $filename);
                        $photoPath = self::PUBLIC_ASSETS_DIR . '/photos/' . $filename;
                        $candidate->photo = $photoPath;
                        $uploadedFiles['candidates_photos'][] = $photoPath;
                    } elseif (isset($oldPhotosByIndex[$index]) && $oldPhotosByIndex[$index]) {
                        $candidate->photo = $oldPhotosByIndex[$index];
                    }
                    $candidate->save();
                }

                $positions = $election->candidates()->distinct()->pluck('position')->toArray();

                if ($request->hasFile('voters_file')) {
                    // En édition, si un fichier Excel est fourni, on remplace la liste des votants
                    // (isEditable() garantit qu'aucun vote n'a été enregistré).
                    $election->votes()->delete();
                    foreach ($election->voters as $existingVoter) {
                        $existingVoter->positions()->delete();
                    }
                    $election->voters()->delete();

                    $votersData = $this->excelImportService->import($request->file('voters_file'));
                    foreach ($votersData as $voterData) {
                        $voteCount = max(1, (int) ($voterData['nombre_voix'] ?? $voterData['vote_count'] ?? 1));
                        $voter = new Voter();
                        $voter->election_id = $election->id;
                        $voter->email = strtolower(trim((string) $voterData['email']));
                        $voter->first_name = $voterData['nom'] ?? $voterData['first_name'] ?? '';
                        $voter->last_name = $voterData['prenom'] ?? $voterData['last_name'] ?? '';
                        $voter->vote_count = $voteCount;
                        $voter->token = Voter::generateToken();
                        $voter->save();

                        foreach ($positions as $position) {
                            $vp = new VoterPosition();
                            $vp->voter_id = $voter->id;
                            $vp->position = $position;
                            $vp->vote_count = $voteCount;
                            $vp->save();
                        }
                    }
                } else {
                    // Sans nouveau fichier, garder les votants et réaligner leurs positions.
                    foreach ($election->voters as $voter) {
                        $voter->positions()->delete();
                        foreach ($positions as $position) {
                            $vp = new VoterPosition();
                            $vp->voter_id = $voter->id;
                            $vp->position = $position;
                            $vp->vote_count = $voter->vote_count ?? 1;
                            $vp->save();
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->cleanupUploadedFiles($uploadedFiles);
            $request->session()->flash('candidates_data', $request->candidates);
            return back()->withErrors(['error' => 'Une erreur est survenue : ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('elections.voir', $election)->with('success', 'Élection mise à jour avec succès.');
    }

    /**
     * Nettoyer les fichiers uploadés en cas d'erreur
     */
    private function cleanupUploadedFiles(array $uploadedFiles)
    {
        try {
            if ($uploadedFiles['logo']) {
                $logoPath = public_path($uploadedFiles['logo']);
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                } elseif (Storage::disk('public')->exists($uploadedFiles['logo'])) {
                    Storage::disk('public')->delete($uploadedFiles['logo']);
                }
            }

            foreach ($uploadedFiles['candidates_photos'] as $photoPath) {
                if ($photoPath) {
                    $fullPath = public_path($photoPath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer
            \Log::warning('Erreur lors du nettoyage des fichiers uploadés: ' . $e->getMessage());
        }
    }

    public function voters(Election $election)
    {
        try {
            if (!Auth::guard('web')->check()) {
                return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
            }
            if ($election->user_id !== Auth::id()) {
                return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
            }

            $election->load(['voters.positions']);
            
            // Récupérer tous les votants avec leur nombre de voix et statut d'envoi d'email
            $voters = $election->voters()->with('positions')->get()->map(function($voter) {
                return [
                    'id' => $voter->id,
                    'full_name' => $voter->full_name,
                    'email' => $voter->email,
                    'vote_count' => $voter->vote_count ?? 0,
                    'has_voted' => $voter->has_voted,
                    'voted_at' => $voter->voted_at,
                    'email_sent_at' => $voter->email_sent_at,
                    'email_error' => $voter->email_error,
                ];
            });

            return view('elections.voters', compact('election', 'voters'));
        } catch (\Exception $e) {
            \Log::error('Erreur dans ElectionController::voters', [
                'election_id' => $election->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('elections.voir', $election)
                ->with('error', 'Erreur lors du chargement de la liste des votants.');
        }
    }

    /**
     * Export Excel de la liste des électeurs (colonnes compatibles modèle + statuts).
     */
    public function exportElecteursExcel(Election $election)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }

        $filename = 'electeurs_election_' . $election->id . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ElecteursListExport($election), $filename);
    }

    /**
     * Modifier le nombre de voix d'un votant (et ses voix par position).
     */
    public function updateVoterVoteCount(Request $request, Election $election, Voter $voter)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if ($voter->election_id !== $election->id) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Votant inconnu pour cette élection.');
        }
        if ($election->isActivated()) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Les voix ne peuvent plus être modifiées après activation de l\'élection.');
        }
        if ($voter->has_voted) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Impossible de modifier les voix d\'un votant ayant déjà voté.');
        }

        $validated = $request->validate([
            'vote_count' => 'required|integer|min:1|max:100',
        ], [
            'vote_count.required' => 'Le nombre de voix est obligatoire.',
            'vote_count.integer' => 'Le nombre de voix doit être un entier.',
            'vote_count.min' => 'Le nombre de voix minimum est 1.',
            'vote_count.max' => 'Le nombre de voix maximum est 100.',
        ]);

        $voteCount = (int) $validated['vote_count'];

        DB::transaction(function () use ($voter, $voteCount) {
            $voter->vote_count = $voteCount;
            $voter->save();

            $voter->positions()->update(['vote_count' => $voteCount]);
        });

        return back()->with('success', 'Nombre de voix mis à jour pour ' . $voter->email . '.');
    }

    /**
     * Envoyer/Renvoyer les liens de vote en masse:
     * - votants en échec d'envoi
     * - votants jamais tentés (en attente)
     */
    public function resendVotingLinks(Election $election)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if (!$election->isActivated()) {
            return redirect()->route('elections.voir', $election)->with('error', 'L\'élection n\'est pas encore activée. Les emails seront envoyés après paiement.');
        }

        $targets = $election->voters()
            ->where(function ($q) {
                $q->whereNull('email_sent_at')
                    ->orWhereNotNull('email_error');
            })
            ->get();
        $sent = 0;
        $stillFailed = 0;

        foreach ($targets as $voter) {
            try {
                $this->emailService->sendVotingLink($voter);
                $voter->email_sent_at = now();
                $voter->email_error = null;
                $voter->save();
                $sent++;
            } catch (\Exception $e) {
                \Log::warning("Renvoyer lien votant {$voter->email}: " . $e->getMessage());
                $voter->email_error = $e->getMessage();
                $voter->save();
                $stillFailed++;
            }
        }

        if ($sent > 0 && $stillFailed === 0) {
            return back()->with('success', "Liens renvoyés à {$sent} votant(s).");
        }
        if ($sent > 0 && $stillFailed > 0) {
            return back()->with('warning', "{$sent} lien(s) renvoyé(s). {$stillFailed} envoi(s) en échec (vérifiez les adresses).");
        }
        if ($stillFailed > 0) {
            return back()->with('error', "Aucun envoi réussi. {$stillFailed} adresse(s) invalide(s) ou erreur SMTP. Modifiez l'élection pour corriger les emails.");
        }

        return back()->with('info', 'Aucun votant en attente ou en échec.');
    }

    /**
     * Modifier l'email d'un votant (pour corriger une erreur puis renvoyer le lien).
     */
    public function updateVoterEmail(Request $request, Election $election, Voter $voter)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if ($voter->election_id !== $election->id) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Votant inconnu pour cette élection.');
        }
        if ($voter->email_sent_at !== null) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Seuls les emails en échec ou non envoyés peuvent être modifiés.');
        }

        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
        ]);

        $newEmail = $request->input('email');
        if ($voter->email !== $newEmail) {
            $exists = Voter::where('election_id', $election->id)->where('id', '!=', $voter->id)->where('email', $newEmail)->exists();
            if ($exists) {
                return back()->with('error', 'Cette adresse email est déjà utilisée pour un autre votant de cette élection.');
            }
            $voter->email = $newEmail;
            $voter->email_sent_at = null;
            $voter->email_error = null;
            $voter->save();
        }

        return back()->with('success', 'Email mis à jour. Vous pouvez renvoyer le lien à ce votant.');
    }

    /**
     * Renvoyer le lien de vote à un seul votant (après correction d'email ou nouveau tentative).
     */
    public function resendVotingLinkToOne(Election $election, Voter $voter)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if ($voter->election_id !== $election->id) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Votant inconnu pour cette élection.');
        }
        if (!$election->isActivated()) {
            return redirect()->route('elections.voir', $election)->with('error', 'L\'élection n\'est pas encore activée.');
        }

        try {
            $this->emailService->sendVotingLink($voter);
            $voter->email_sent_at = now();
            $voter->email_error = null;
            $voter->save();
            return back()->with('success', 'Lien renvoyé à ' . $voter->email . '.');
        } catch (\Exception $e) {
            \Log::warning("Renvoyer lien votant {$voter->email}: " . $e->getMessage());
            $voter->email_sent_at = null;
            $voter->email_error = $e->getMessage();
            $voter->save();
            return back()->with('error', 'Échec d\'envoi : ' . $e->getMessage() . '. Vérifiez l\'adresse email.');
        }
    }

    /**
     * Supprimer un votant de la liste (admin propriétaire).
     */
    public function destroyVoter(Election $election, Voter $voter)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('administration.connexion')->with('error', 'Accès non autorisé.');
        }
        if ($election->user_id !== Auth::id()) {
            return redirect()->route('elections.liste')->with('error', 'Accès non autorisé.');
        }
        if ($voter->election_id !== $election->id) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Votant inconnu pour cette élection.');
        }
        if ($voter->has_voted) {
            return redirect()->route('elections.electeurs', $election)->with('error', 'Impossible de supprimer un votant ayant déjà voté.');
        }

        DB::transaction(function () use ($voter) {
            $voter->positions()->delete();
            $voter->delete();
        });

        return back()->with('success', 'Votant supprimé avec succès.');
    }
}

