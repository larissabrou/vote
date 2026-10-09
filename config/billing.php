<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paiement des élections
    |--------------------------------------------------------------------------
    |
    | Quand ce réglage est à false, aucune page de paiement n'est accessible :
    | toute élection créée est active d'office, les liens de vote partent dès
    | la création et les votants peuvent voter sans qu'aucun paiement n'ait eu
    | lieu. Mettre BILLING_ENABLED=true dans l'environnement pour rétablir le
    | paiement Afribapay sans toucher au code.
    |
    */

    'enabled' => filter_var(env('BILLING_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

];
